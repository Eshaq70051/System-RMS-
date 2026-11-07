<?php
$pageTitle = 'شاشة المطبخ الذكية (KDS)';
$pageSubtitle = 'متابعة الطلبات المباشرة، خطوط الإنتاج والتحضير الفوري';
ob_start();
?>

<!-- KDS Top Bar Controls -->
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 bg-slate-900 text-white p-4 rounded-2xl shadow-lg border border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-md shadow-rose-500/30">
            <i class="fa-solid fa-fire-burner"></i>
        </div>
        <div>
            <h3 class="font-black text-base">محطة تحضير الطلبات (شاشة المطبخ)</h3>
            <span class="text-xs text-slate-400">تحديث تلقائي كل 5 ثوانٍ مع تنبيه صوتي فوري</span>
        </div>
    </div>

    <div class="flex items-center gap-3 text-xs">
        <!-- Live status count badges -->
        <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">
            انتظار: <b id="countWaiting">0</b>
        </span>
        <span class="px-3 py-1.5 rounded-xl bg-blue-500/20 text-blue-300 font-bold border border-blue-500/30">
            قيد الطهي: <b id="countCooking">0</b>
        </span>
        <span class="px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
            جاهز للتسليم: <b id="countReady">0</b>
        </span>

        <!-- Audio mute toggle -->
        <button onclick="toggleAudio()" id="btnAudioToggle" class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white font-bold flex items-center gap-1.5 border border-slate-700 transition">
            <i class="fa-solid fa-bell text-amber-400"></i>
            <span>تنبيه صوتي</span>
        </button>

        <button onclick="fetchKdsOrders()" class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white transition" title="تحديث يدوي">
            <i class="fa-solid fa-rotate"></i>
        </button>
    </div>
</div>

<!-- KDS Tickets Grid -->
<div id="kdsTicketsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Tickets dynamically rendered by JS -->
    <div class="col-span-full py-16 text-center text-slate-400">
        <i class="fa-solid fa-utensils text-4xl mb-3 text-slate-300 animate-bounce"></i>
        <p class="font-bold text-sm">جاري جلب طلبات المطبخ...</p>
    </div>
</div>

<!-- Audio element for new order alert -->
<audio id="orderAudioAlert" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<script>
    let knownOrderIds = new Set();
    let audioEnabled = true;

    function toggleAudio() {
        audioEnabled = !audioEnabled;
        const btn = document.getElementById('btnAudioToggle');
        if (audioEnabled) {
            btn.innerHTML = '<i class="fa-solid fa-bell text-amber-400"></i> <span>تنبيه صوتي: مفعل</span>';
        } else {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash text-slate-500"></i> <span>تنبيه صوتي: معطل</span>';
        }
    }

    async function fetchKdsOrders() {
        try {
            const res = await fetch('<?= base_url('kds/orders') ?>');
            const data = await res.json();

            if (!data.success) return;

            renderTickets(data.orders);

            // Check if there are new orders that weren't known before
            let hasNew = false;
            data.orders.forEach(o => {
                if (!knownOrderIds.has(o.id)) {
                    hasNew = true;
                    knownOrderIds.add(o.id);
                }
            });

            if (hasNew && audioEnabled && knownOrderIds.size > data.orders.length) {
                const audio = document.getElementById('orderAudioAlert');
                if (audio) {
                    audio.play().catch(() => {});
                }
            }

        } catch (e) {
            console.error('KDS Fetch Error:', e);
        }
    }

    function renderTickets(orders) {
        const grid = document.getElementById('kdsTicketsGrid');
        let waiting = 0, cooking = 0, ready = 0;

        if (orders.length === 0) {
            grid.innerHTML = `
                <div class="col-span-full py-20 text-center text-slate-400">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-3">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h4 class="text-base font-bold text-slate-700">لا توجد طلبات معلقة في المطبخ الآن!</h4>
                    <p class="text-xs text-slate-400 mt-1">جميع الطلبات تم طهيها وتسليمها بنجاح</p>
                </div>
            `;
            document.getElementById('countWaiting').textContent = 0;
            document.getElementById('countCooking').textContent = 0;
            document.getElementById('countReady').textContent = 0;
            return;
        }

        let html = '';
        orders.forEach(order => {
            if (order.kitchen_status === 'waiting') waiting++;
            else if (order.kitchen_status === 'cooking') cooking++;
            else if (order.kitchen_status === 'done') ready++;

            // Ticket Header Color based on Status & Elapsed time
            let headerBg = 'bg-slate-800';
            let badgeBg = 'bg-slate-700 text-slate-200';
            let statusText = 'انتظار';

            if (order.kitchen_status === 'waiting') {
                headerBg = order.elapsed_minutes > 15 ? 'bg-rose-700' : 'bg-amber-600';
                badgeBg = 'bg-black/30 text-white';
                statusText = 'بانتظار البدء';
            } else if (order.kitchen_status === 'cooking') {
                headerBg = 'bg-blue-600';
                badgeBg = 'bg-blue-900 text-blue-100';
                statusText = 'قيد الطهي';
            } else if (order.kitchen_status === 'done') {
                headerBg = 'bg-emerald-600';
                badgeBg = 'bg-emerald-900 text-emerald-100';
                statusText = 'جاهز للتسليم';
            }

            // Items list
            let itemsHtml = '';
            order.items.forEach(it => {
                itemsHtml += `
                    <div class="flex items-start justify-between py-2 border-b border-slate-100 last:border-0 text-xs">
                        <div class="flex items-start gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 font-black flex items-center justify-center flex-shrink-0 text-xs">${it.quantity}x</span>
                            <div>
                                <span class="font-bold text-slate-800 block">${it.product_name}</span>
                                ${it.notes ? `<span class="text-[10px] text-amber-600 block font-semibold"><i class="fa-solid fa-pen"></i> ${it.notes}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            // Action button
            let actionBtn = '';
            if (order.kitchen_status === 'waiting') {
                actionBtn = `<button onclick="updateTicketStatus(${order.id}, 'cooking')" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5"><i class="fa-solid fa-play"></i> بدء التحضير</button>`;
            } else if (order.kitchen_status === 'cooking') {
                actionBtn = `<button onclick="updateTicketStatus(${order.id}, 'done')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5"><i class="fa-solid fa-check"></i> جاهز للتسليم</button>`;
            } else if (order.kitchen_status === 'done') {
                actionBtn = `<button onclick="updateTicketStatus(${order.id}, 'served')" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs rounded-xl shadow transition flex items-center justify-center gap-1.5"><i class="fa-solid fa-bell-concierge"></i> تم التسليم للزبون</button>`;
            }

            html += `
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Ticket Header -->
                        <div class="${headerBg} text-white p-3 text-xs flex items-center justify-between transition">
                            <div>
                                <span class="font-black text-sm block">#${order.order_number}</span>
                                <span class="text-[10px] opacity-80">${order.order_type === 'dine_in' ? `طاولة: ${order.table_number || '-'}` : (order.order_type === 'takeaway' ? 'سفري' : 'توصيل')}</span>
                            </div>
                            <div class="text-left">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${badgeBg} block mb-0.5">${statusText}</span>
                                <span class="text-[11px] font-mono font-bold flex items-center gap-1"><i class="fa-regular fa-clock"></i> ${order.elapsed_minutes} دقيقة</span>
                            </div>
                        </div>

                        <!-- Ticket Kitchen Notes -->
                        ${order.kitchen_notes ? `<div class="p-2 bg-amber-50 text-amber-800 border-b border-amber-200 text-[11px] font-bold"><i class="fa-solid fa-circle-info"></i> ${order.kitchen_notes}</div>` : ''}

                        <!-- Items List -->
                        <div class="p-3.5 space-y-1">
                            ${itemsHtml}
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-3 bg-slate-50 border-t border-slate-100">
                        ${actionBtn}
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
        document.getElementById('countWaiting').textContent = waiting;
        document.getElementById('countCooking').textContent = cooking;
        document.getElementById('countReady').textContent = ready;
    }

    async function updateTicketStatus(orderId, status) {
        try {
            const res = await fetch('<?= base_url('kds/update-status') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: status })
            });
            const data = await res.json();
            if (data.success) {
                fetchKdsOrders();
            }
        } catch (e) {
            console.error('Update status error:', e);
        }
    }

    // Initial fetch and 5-second polling interval
    fetchKdsOrders();
    setInterval(fetchKdsOrders, 5000);
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
