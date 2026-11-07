<?php

namespace App\Controllers;

use App\Models\Database;

class EmployeeController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;

        $employees = $this->db->fetchAll(
            "SELECT e.*, b.name as branch_name 
             FROM employees e 
             JOIN branches b ON e.branch_id = b.id 
             WHERE e.branch_id = :bid 
             ORDER BY e.id DESC",
            ['bid' => $branchId]
        );

        // Stats
        $totalStaff = count($employees);
        $totalSalaries = array_sum(array_column($employees, 'salary'));

        require_once __DIR__ . '/../../views/employees/index.php';
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('employees');
        }

        $branchId = auth_user()['branch_id'] ?? 1;
        $name = trim($_POST['full_name'] ?? '');
        $nationalId = trim($_POST['national_id'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $department = trim($_POST['department'] ?? 'الخدمة والصالة');
        $salary = (float)($_POST['salary'] ?? 0);
        $hireDate = !empty($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d');
        $emergencyPhone = trim($_POST['emergency_phone'] ?? '');

        if (empty($name) || empty($phone) || empty($jobTitle)) {
            set_flash('error', 'يرجى ملء الحقول المطلوبة (الاسم، الهاتف، المسمى الوظيفي)');
            redirect('employees');
        }

        $this->db->insert('employees', [
            'branch_id' => $branchId,
            'full_name' => $name,
            'national_id' => $nationalId,
            'phone' => $phone,
            'job_title' => $jobTitle,
            'department' => $department,
            'salary' => $salary,
            'hire_date' => $hireDate,
            'emergency_phone' => $emergencyPhone,
            'is_active' => 1
        ]);

        log_audit('إضافة موظف', 'employees', "إضافة الموظف {$name} ({$jobTitle})");
        set_flash('success', 'تمت إضافة بيانات الموظف بنجاح');
        redirect('employees');
    }

    /**
     * Attendance log
     */
    public function attendance(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;
        $date = $_GET['date'] ?? date('Y-m-d');

        $attendances = $this->db->fetchAll(
            "SELECT ea.*, e.full_name, e.job_title, e.department 
             FROM employees e 
             LEFT JOIN employee_attendances ea ON e.id = ea.employee_id AND ea.date = :date 
             WHERE e.branch_id = :bid AND e.is_active = 1 
             ORDER BY e.full_name ASC",
            ['date' => $date, 'bid' => $branchId]
        );

        require_once __DIR__ . '/../../views/employees/attendance.php';
    }

    public function recordAttendance(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('attendance');
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'present';
        $clockIn = !empty($_POST['clock_in']) ? $_POST['clock_in'] : null;
        $clockOut = !empty($_POST['clock_out']) ? $_POST['clock_out'] : null;
        $notes = trim($_POST['notes'] ?? '');

        $existing = $this->db->fetch(
            "SELECT id FROM employee_attendances WHERE employee_id = :eid AND date = :date",
            ['eid' => $employeeId, 'date' => $date]
        );

        $workingHours = 8.0;
        if ($clockIn && $clockOut) {
            $diff = strtotime($clockOut) - strtotime($clockIn);
            $workingHours = max(0, round($diff / 3600, 2));
        }

        if ($existing) {
            $this->db->update('employee_attendances', [
                'status' => $status,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'working_hours' => $workingHours,
                'notes' => $notes
            ], 'id = :id', ['id' => $existing['id']]);
        } else {
            $this->db->insert('employee_attendances', [
                'employee_id' => $employeeId,
                'date' => $date,
                'status' => $status,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'working_hours' => $workingHours,
                'notes' => $notes
            ]);
        }

        set_flash('success', 'تم حفظ تسجيل الحضور بنجاح');
        redirect('attendance?date=' . $date);
    }

    /**
     * Payroll Generation
     */
    public function payroll(): void
    {
        $branchId = auth_user()['branch_id'] ?? 1;
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $payrolls = $this->db->fetchAll(
            "SELECT ep.*, e.full_name, e.job_title, e.department, e.salary as current_basic 
             FROM employee_payrolls ep 
             JOIN employees e ON ep.employee_id = e.id 
             WHERE ep.month = :m AND ep.year = :y AND e.branch_id = :bid 
             ORDER BY ep.id DESC",
            ['m' => $month, 'y' => $year, 'bid' => $branchId]
        );

        $employees = $this->db->fetchAll(
            "SELECT * FROM employees WHERE branch_id = :bid AND is_active = 1 ORDER BY full_name ASC",
            ['bid' => $branchId]
        );

        require_once __DIR__ . '/../../views/employees/payroll.php';
    }

    public function generatePayroll(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'رمز التحقق غير صالح');
            redirect('payroll');
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $month = (int)($_POST['month'] ?? date('m'));
        $year = (int)($_POST['year'] ?? date('Y'));
        $bonuses = (float)($_POST['bonuses'] ?? 0);
        $deductions = (float)($_POST['deductions'] ?? 0);
        $advances = (float)($_POST['advances'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        $emp = $this->db->fetch("SELECT * FROM employees WHERE id = :id", ['id' => $employeeId]);
        if (!$emp) {
            set_flash('error', 'الموظف غير موجود');
            redirect('payroll');
        }

        $basic = (float)$emp['salary'];
        $net = max(0, $basic + $bonuses - $deductions - $advances);

        $existing = $this->db->fetch(
            "SELECT id FROM employee_payrolls WHERE employee_id = :eid AND month = :m AND year = :y",
            ['eid' => $employeeId, 'm' => $month, 'y' => $year]
        );

        $data = [
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $basic,
            'bonuses' => $bonuses,
            'deductions' => $deductions,
            'advances' => $advances,
            'net_salary' => $net,
            'payment_status' => 'paid',
            'payment_date' => date('Y-m-d'),
            'notes' => $notes
        ];

        if ($existing) {
            $this->db->update('employee_payrolls', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            $this->db->insert('employee_payrolls', $data);
        }

        log_audit('صرف راتب', 'employees', "صرف راتب شهر {$month}/{$year} للموظف {$emp['full_name']} بصافي {$net} ر.س");
        set_flash('success', "تم اعتماد وصرف مسير الراتب للموظف {$emp['full_name']} بنجاح");
        redirect("payroll?month={$month}&year={$year}");
    }
}
