@php
    $adminUser = Auth::guard('admin')->user() ?? Auth::user();
@endphp

<nav class="px-4 py-0 navbar navbar-expand navbar-light sticky-top" style="background-color: #fff !important; border-bottom: 1px solid #e2e8f0;">

    <div class="p-2 mr-4 navbar-nav align-items-center">
        <a href="#" class="flex-shrink-0 sidebar-toggler">
             <i class="bi bi-list toggle-sidebar-btn" style="font-size:22px;color:#1e293b;"></i>
        </a>
    </div>

    <h4 style="color:#1e293b;margin:0;font-family:'Inter',sans-serif;font-size:16px;font-weight:600;">
        @yield('title', 'Dashboard')
    </h4>

    <div class="p-1 mr-4 navbar-nav align-items-center ms-auto">
        <div class="dropdown me-3 admin-activity-wrap" id="adminActivityCenter" data-admin-id="{{ $adminUser?->id }}">
            <button class="admin-activity-trigger position-relative"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Open admin notifications">
                <i id="adminActivityBellIcon" class="bi bi-bell"></i>
                <span id="adminActivityBadge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill d-none">
                    0
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0 admin-activity-menu">
                <div class="admin-activity-menu-header">
                    <strong>Activity Notifications</strong>
                    <button type="button" class="btn btn-link p-0 text-decoration-none admin-mark-all-btn" id="adminMarkAllSeen">
                        Mark all read
                    </button>
                </div>
                <div id="adminActivitySummary" class="admin-activity-summary">
                    <span class="text-muted">Loading summary...</span>
                </div>
                <div id="adminActivityList" class="admin-activity-list">
                    <div class="px-3 py-3 text-muted" style="font-size:12px;">Loading activities...</div>
                </div>
            </div>
        </div>

        <div class="profile d-flex align-items-center">
             <div class="d-none d-lg-block name ps-2 text-end pe-3">
                 <h4 class="m-0" style="font-size: 14px;color:#1e293b;font-family:'Inter',sans-serif;font-weight:600;">{{ $adminUser?->name }}</h4>
                 <small style="color:#64748b;font-size:12px;">admin</small>
             </div>
             <img src="{{ asset('backend/img/user.jpg') }}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;" alt="profile">
        </div>
    </div>
</nav>

<script>
    (function () {
        var center = document.getElementById('adminActivityCenter');
        if (!center) {
            return;
        }

        var feedUrl = @json(route('admin.activity-feed'));
        var adminId = center.getAttribute('data-admin-id') || '0';
        var seenStorageKey = 'admin-activity-seen-' + adminId;
        var badgeEl = document.getElementById('adminActivityBadge');
        var bellIconEl = document.getElementById('adminActivityBellIcon');
        var listEl = document.getElementById('adminActivityList');
        var summaryEl = document.getElementById('adminActivitySummary');
        var markAllBtn = document.getElementById('adminMarkAllSeen');
        var latestItems = [];

        function readSeen() {
            try {
                var raw = localStorage.getItem(seenStorageKey);
                var parsed = raw ? JSON.parse(raw) : [];
                return new Set(Array.isArray(parsed) ? parsed : []);
            } catch (e) {
                return new Set();
            }
        }

        function writeSeen(set) {
            var values = Array.from(set);
            if (values.length > 500) {
                values = values.slice(values.length - 500);
            }
            localStorage.setItem(seenStorageKey, JSON.stringify(values));
        }

        function markSeen(id) {
            if (!id) {
                return;
            }
            var seen = readSeen();
            seen.add(id);
            writeSeen(seen);
        }

        function markAllSeen(items) {
            var seen = readSeen();
            items.forEach(function (item) {
                if (item && item.id) {
                    seen.add(item.id);
                }
            });
            writeSeen(seen);
        }

        function formatTime(value) {
            if (!value) {
                return '';
            }
            var date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            return date.toLocaleString();
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function updateBadge() {
            var seen = readSeen();
            var unread = latestItems.reduce(function (count, item) {
                return seen.has(item.id) ? count : count + 1;
            }, 0);

            if (unread > 0) {
                badgeEl.textContent = unread > 99 ? '99+' : String(unread);
                badgeEl.classList.remove('d-none');
                center.classList.add('has-unread');
                if (bellIconEl) {
                    bellIconEl.classList.remove('bi-bell');
                    bellIconEl.classList.add('bi-bell-fill');
                }
            } else {
                badgeEl.classList.add('d-none');
                center.classList.remove('has-unread');
                if (bellIconEl) {
                    bellIconEl.classList.remove('bi-bell-fill');
                    bellIconEl.classList.add('bi-bell');
                }
            }
        }

        function renderSummary(summary) {
            if (!summary) {
                summaryEl.innerHTML = '<span class="text-muted">No summary available.</span>';
                return;
            }

            summaryEl.innerHTML =
                '<div class="summary-chip"><span class="count">' + (summary.pending_suppliers || 0) + '</span><span>Pending suppliers</span></div>' +
                '<div class="summary-chip"><span class="count">' + (summary.pending_payout_requests || 0) + '</span><span>Pending payouts</span></div>' +
                '<div class="summary-chip"><span class="count">' + (summary.today_orders || 0) + '</span><span>Orders today</span></div>';
        }

        function renderItems(items) {
            if (!Array.isArray(items) || items.length === 0) {
                listEl.innerHTML = '<div class="px-3 py-3 text-muted" style="font-size:12px;">No recent activity found.</div>';
                return;
            }

            var seen = readSeen();
            var html = items.map(function (item) {
                var unreadClass = seen.has(item.id) ? '' : 'unread';
                var scopeLabel = item.scope === 'supplier' ? 'Supplier' : 'User';
                var scopeClass = item.scope === 'supplier' ? 'scope-supplier' : 'scope-user';
                var safeUrl = escapeHtml(item.url || '');
                return '' +
                    '<div data-id="' + escapeHtml(item.id) + '"' +
                        ' data-title="' + escapeHtml(item.title || 'Activity') + '"' +
                        ' data-message="' + escapeHtml(item.message || '') + '"' +
                        ' data-time="' + escapeHtml(formatTime(item.created_at)) + '"' +
                        ' data-url="' + safeUrl + '"' +
                        ' class="admin-activity-item ' + unreadClass + '" style="white-space:normal;cursor:pointer;">' +
                        '<div class="d-flex align-items-start justify-content-between gap-2 mb-1">' +
                            '<div class="activity-title">' + escapeHtml(item.title || 'Activity') + '</div>' +
                            '<span class="activity-scope ' + scopeClass + '">' + scopeLabel + '</span>' +
                        '</div>' +
                        '<div class="activity-message">' + escapeHtml(item.message || '') + '</div>' +
                        '<div class="activity-time">' + escapeHtml(formatTime(item.created_at)) + '</div>' +
                    '</div>';
            }).join('');

            listEl.innerHTML = html;
        }

        function fetchFeed() {
            $.ajax({
                url: feedUrl,
                method: 'GET',
                data: { limit: 20 },
                success: function (response) {
                    latestItems = Array.isArray(response && response.data) ? response.data : [];
                    renderSummary(response ? response.summary : null);
                    renderItems(latestItems);
                    updateBadge();
                },
                error: function () {
                    listEl.innerHTML = '<div class="px-3 py-3 text-danger" style="font-size:12px;">Failed to load activities.</div>';
                }
            });
        }

        markAllBtn.addEventListener('click', function (event) {
            event.preventDefault();
            markAllSeen(latestItems);
            renderItems(latestItems);
            updateBadge();
        });

        listEl.addEventListener('click', function (event) {
            var target = event.target.closest('.admin-activity-item');
            if (!target) {
                return;
            }
            event.preventDefault();
            var itemId = target.getAttribute('data-id');
            markSeen(itemId);
            target.classList.remove('unread');
            updateBadge();

            // Populate and show the notification detail modal
            var modalEl = document.getElementById('adminNotifDetailModal');
            if (modalEl) {
                document.getElementById('adminNotifModalTitle').textContent = target.getAttribute('data-title') || 'Notification';
                document.getElementById('adminNotifModalMessage').textContent = target.getAttribute('data-message') || '';
                document.getElementById('adminNotifModalTime').textContent = target.getAttribute('data-time') || '';
                var linkEl = document.getElementById('adminNotifModalLink');
                var itemUrl = target.getAttribute('data-url');
                if (itemUrl) {
                    linkEl.href = itemUrl;
                    linkEl.style.display = 'inline-flex';
                } else {
                    linkEl.style.display = 'none';
                }
                var bsModal = new bootstrap.Modal(modalEl);
                bsModal.show();
            }
        });

        fetchFeed();
        setInterval(fetchFeed, 15000);
    })();
</script>

<style>
    .admin-activity-wrap .admin-activity-trigger {
        width: 40px;
        height: 40px;
        padding: 0;
        cursor: pointer;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #ffffff;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        transition: all .18s ease;
    }

    .admin-activity-wrap .admin-activity-trigger i {
        font-size: 18px;
        line-height: 1;
    }

    .admin-activity-wrap .admin-activity-trigger:hover {
        border-color: #c7d2fe;
        background: #f8faff;
        color: #1e3a8a;
    }

    .admin-activity-wrap .admin-activity-trigger:focus,
    .admin-activity-wrap .admin-activity-trigger:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }

    .admin-activity-wrap.show .admin-activity-trigger,
    .admin-activity-wrap.has-unread .admin-activity-trigger {
        border-color: #93c5fd;
        background: #eff6ff;
        color: #1d4ed8;
    }

    #adminActivityBadge {
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        min-width: 19px;
        height: 19px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        font-weight: 700;
    }

    .admin-activity-wrap .admin-activity-menu {
        width: min(92vw, 430px);
        margin-top: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 20px 42px rgba(15, 23, 42, 0.2);
        overflow: hidden;
    }

    .admin-activity-menu-header {
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(180deg, #f8fbff 0%, #f2f6ff 100%);
        border-bottom: 1px solid #e5e7eb;
    }

    .admin-activity-menu-header strong {
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .1px;
    }

    .admin-mark-all-btn {
        font-size: 12px;
        color: #1d4ed8;
        font-weight: 600;
    }

    .admin-mark-all-btn:hover {
        color: #1e40af;
    }

    .admin-activity-summary {
        padding: 10px 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        border-bottom: 1px solid #eef2f7;
        background: #ffffff;
    }

    .admin-activity-summary .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e5eaf1;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        color: #475569;
        line-height: 1;
        white-space: nowrap;
    }

    .admin-activity-summary .summary-chip .count {
        color: #0f172a;
        font-weight: 700;
    }

    .admin-activity-list {
        max-height: 390px;
        overflow-y: auto;
        background: #fff;
    }

    .admin-activity-item {
        display: block;
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        transition: all .16s ease;
        text-decoration: none;
    }

    .admin-activity-item:last-child {
        border-bottom: 0;
    }

    .admin-activity-item.unread {
        background: #f8faff;
        border-left: 3px solid #60a5fa;
        padding-left: 11px;
    }

    .admin-activity-item:hover {
        background: #f8fafc;
    }

    .admin-activity-item .activity-title {
        font-size: 13px;
        line-height: 1.25;
        color: #0f172a;
        font-weight: 700;
    }

    .admin-activity-item .activity-message {
        font-size: 12px;
        color: #475569;
        line-height: 1.4;
    }

    .admin-activity-item .activity-time {
        margin-top: 2px;
        font-size: 11px;
        color: #94a3b8;
    }

    .admin-activity-item .activity-scope {
        border-radius: 999px;
        padding: 2px 9px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.3;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .admin-activity-item .activity-scope.scope-user {
        color: #1e3a8a;
        background: #dbeafe;
        border-color: #bfdbfe;
    }

    .admin-activity-item .activity-scope.scope-supplier {
        color: #065f46;
        background: #d1fae5;
        border-color: #a7f3d0;
    }

    .admin-activity-list::-webkit-scrollbar {
        width: 8px;
    }

    .admin-activity-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .admin-activity-list::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    /* Notification Detail Modal */
    #adminNotifDetailModal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    }
    #adminNotifDetailModal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 16px 20px;
    }
    #adminNotifDetailModal .modal-body {
        padding: 20px;
    }
    #adminNotifDetailModal .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 12px 20px;
    }
    #adminNotifModalMessage {
        white-space: pre-line;
        color: #475569;
        font-size: 14px;
        line-height: 1.6;
    }
    #adminNotifModalTime {
        color: #94a3b8;
        font-size: 12px;
    }
    #adminNotifModalLink {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #4f46e5;
        text-decoration: none;
        margin-top: 8px;
    }
    #adminNotifModalLink:hover {
        color: #3730a3;
    }
</style>

<!-- Notification Detail Modal -->
<div class="modal fade" id="adminNotifDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="font-size:15px;">Notification</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="adminNotifModalTitle" class="fw-semibold mb-2" style="font-size:17px;color:#0f172a;"></h5>
                <p id="adminNotifModalMessage" class="mb-2"></p>
                <p id="adminNotifModalTime" class="mb-0"></p>
                <a id="adminNotifModalLink" href="#" target="_self">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                    View Details
                </a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
