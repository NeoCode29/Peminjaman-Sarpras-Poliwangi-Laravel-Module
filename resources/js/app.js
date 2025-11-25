const initSidebar = () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggleButton = document.querySelector('[data-sidebar-toggle]');
    const panel = document.querySelector('[data-sidebar-panel]');
    const toggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const filterToggle = document.querySelector('[data-filter-toggle]');
    const filterPanel = document.querySelector('[data-filter-panel]');

    if (!sidebar || !toggleButton || !panel || !toggleIcon || !overlay) {
        return;
    }

    const toggleState = (isOpen) => {
        toggleButton.setAttribute('aria-expanded', String(isOpen));
        panel.classList.toggle('sidebar__panel--open', isOpen);
        toggleIcon.classList.toggle('sidebar__toggle-icon--open', isOpen);
        overlay.classList.toggle('app-shell__overlay--visible', isOpen);
        document.body.classList.toggle('app-shell--sidebar-open', isOpen);
    };

    toggleButton.addEventListener('click', () => {
        const nextState = !panel.classList.contains('sidebar__panel--open');
        toggleState(nextState);
    });

    overlay.addEventListener('click', () => toggleState(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            toggleState(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            toggleState(false);
        }
    });

    if (filterToggle && filterPanel) {
        const toggleFilters = (isVisible) => {
            filterToggle.setAttribute('aria-expanded', String(isVisible));
            filterPanel.toggleAttribute('hidden', !isVisible);
        };

        filterToggle.addEventListener('click', () => {
            const nextState = filterPanel.hasAttribute('hidden');
            toggleFilters(nextState);
        });
    }
};

const initPasswordToggle = () => {
    const passwordFields = document.querySelectorAll('[data-password-field]');

    passwordFields.forEach((field) => {
        const input = field.querySelector('[data-password-input]');
        const toggle = field.querySelector('[data-password-toggle]');

        if (!input || !toggle) {
            return;
        }

        const icons = {
            show: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1.5 12s3-7.5 10.5-7.5S22.5 12 22.5 12s-3 7.5-10.5 7.5S1.5 12 1.5 12Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            hide: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l18 18"/><path d="M10.73 5.08A9.76 9.76 0 0 1 12 4.5c7.5 0 10.5 7.5 10.5 7.5a17.38 17.38 0 0 1-3.5 4.79"/><path d="M6.12 6.17C3.3 8.29 1.5 12 1.5 12s3 7.5 10.5 7.5a9.56 9.56 0 0 0 2.62-.37"/><path d="M9.5 9.54a3 3 0 0 0 4.96 3.16"/></svg>'
        };

        const updateToggle = (isVisible) => {
            toggle.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
            toggle.innerHTML = isVisible ? icons.hide : icons.show;
            toggle.setAttribute('data-password-visible', String(isVisible));
        };

        updateToggle(false);

        toggle.addEventListener('click', () => {
            const isVisible = input.getAttribute('type') === 'text';
            const nextVisible = !isVisible;
            input.setAttribute('type', nextVisible ? 'text' : 'password');
            updateToggle(nextVisible);
        });
    });
};

const initFilePreview = () => {
    const fileInputs = document.querySelectorAll('[data-file-input]');

    fileInputs.forEach((input) => {
        const previewContainer = input.closest('.c-file')?.querySelector('[data-file-preview]');

        if (!previewContainer) {
            return;
        }

        const formatFileSize = (bytes) => {
            if (bytes === 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            const index = Math.floor(Math.log(bytes) / Math.log(1024));
            return `${(bytes / Math.pow(1024, index)).toFixed(1)} ${units[index]}`;
        };

        input.addEventListener('change', () => {
            const files = Array.from(input.files ?? []);

            if (files.length === 0) {
                previewContainer.innerHTML = '';
                previewContainer.hidden = true;
                return;
            }

            previewContainer.innerHTML = files
                .map((file) => {
                    const size = formatFileSize(file.size);
                    const extCandidate = file.name.includes('.') ? file.name.split('.').pop() ?? '' : '';
                    const extension = extCandidate.trim() !== '' ? extCandidate.toUpperCase() : 'FILE';
                    return `
                        <div class="c-file__preview-item">
                            <span class="c-file__preview-icon" aria-hidden="true">📄</span>
                            <div class="c-file__preview-meta">
                                <div class="c-file__preview-header">
                                    <span class="c-file__preview-name" title="${file.name}">${file.name}</span>
                                    <span class="c-file__preview-ext">${extension}</span>
                                </div>
                                <small>${size}</small>
                            </div>
                        </div>
                    `;
                })
                .join('');

            previewContainer.hidden = false;
        });
    });
};

const initModal = () => {
    const modals = document.querySelectorAll('[data-modal]');

    modals.forEach((modal) => {
        const backdrop = modal.querySelector('[data-modal-backdrop]');
        const closeButtons = modal.querySelectorAll('[data-modal-close]');
        const isStatic = modal.hasAttribute('data-modal-static');

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        // Close buttons
        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        // Backdrop click (if not static)
        if (backdrop && !isStatic) {
            backdrop.addEventListener('click', closeModal);
        }

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        // Global API
        modal.open = openModal;
        modal.close = closeModal;
    });
};

const initDropdown = () => {
    const dropdowns = document.querySelectorAll('[data-dropdown]');

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!trigger || !menu) return;

        const toggleDropdown = () => {
            const isOpen = dropdown.classList.contains('is-open');
            
            // Close all other dropdowns
            document.querySelectorAll('[data-dropdown].is-open').forEach((other) => {
                if (other !== dropdown) {
                    other.classList.remove('is-open');
                }
            });

            dropdown.classList.toggle('is-open', !isOpen);
        };

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
            }
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdown.classList.contains('is-open')) {
                dropdown.classList.remove('is-open');
                trigger.focus();
            }
        });
    });
};

const initTabs = () => {
    const tabGroups = document.querySelectorAll('[data-tabs]');

    tabGroups.forEach((tabGroup) => {
        const tabs = tabGroup.querySelectorAll('[data-tab-target]');
        const panels = tabGroup.querySelectorAll('[role="tabpanel"]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const targetId = tab.getAttribute('data-tab-target');
                const targetPanel = tabGroup.querySelector(`#${targetId}`);

                if (!targetPanel) return;

                // Update tabs
                tabs.forEach((t) => {
                    t.classList.remove('c-tabs__tab--active');
                    t.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('c-tabs__tab--active');
                tab.setAttribute('aria-selected', 'true');

                // Update panels
                panels.forEach((p) => {
                    p.classList.remove('c-tabs__panel--active');
                    p.setAttribute('hidden', '');
                });
                targetPanel.classList.add('c-tabs__panel--active');
                targetPanel.removeAttribute('hidden');
            });
        });
    });
};

const initAlert = () => {
    const alerts = document.querySelectorAll('[data-alert]');

    alerts.forEach((alert) => {
        const closeButton = alert.querySelector('[data-alert-close]');

        if (!closeButton) return;

        closeButton.addEventListener('click', () => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            alert.style.transition = 'opacity 200ms ease, transform 200ms ease';

            setTimeout(() => {
                alert.remove();
            }, 200);
        });
    });
};

const initToast = () => {
    const toasts = document.querySelectorAll('[data-toast]');

    toasts.forEach((toast) => {
        const closeButton = toast.querySelector('[data-toast-close]');
        const duration = parseInt(toast.getAttribute('data-toast-duration') || '5000', 10);

        const closeToast = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

            setTimeout(() => {
                toast.remove();
            }, 200);
        };

        // Auto close
        if (duration > 0) {
            setTimeout(closeToast, duration);
        }

        // Manual close
        if (closeButton) {
            closeButton.addEventListener('click', closeToast);
        }
    });
};

// Toast API untuk membuat toast secara programmatic
window.createToast = (options = {}) => {
    const {
        type = 'info',
        title = '',
        message = '',
        duration = 5000,
        dismissible = true
    } = options;

    const container = document.querySelector('.c-toast-container') || (() => {
        const div = document.createElement('div');
        div.className = 'c-toast-container';
        document.body.appendChild(div);
        return div;
    })();

    const typeIcons = {
        info: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>',
        success: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>',
        danger: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
    };

    const toast = document.createElement('div');
    toast.className = `c-toast c-toast--${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('data-toast', '');
    if (duration > 0) toast.setAttribute('data-toast-duration', String(duration));

    toast.innerHTML = `
        <div class="c-toast__icon" aria-hidden="true">
            ${typeIcons[type] || typeIcons.info}
        </div>
        <div class="c-toast__content">
            ${title ? `<h4 class="c-toast__title">${title}</h4>` : ''}
            <div class="c-toast__message">${message}</div>
        </div>
        ${dismissible ? `
            <button type="button" class="c-toast__close" data-toast-close aria-label="Tutup notifikasi">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        ` : ''}
    `;

    container.appendChild(toast);

    const closeToast = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 200);
    };

    if (duration > 0) {
        setTimeout(closeToast, duration);
    }

    const closeButton = toast.querySelector('[data-toast-close]');
    if (closeButton) {
        closeButton.addEventListener('click', closeToast);
    }

    return toast;
};

// ====================================
// Calendar Dashboard Component
// ====================================
function initCalendarDashboard() {
    const calendars = document.querySelectorAll('[data-dashboard-calendar]');
    
    calendars.forEach(calendar => {
        const monthLabel = calendar.querySelector('[data-calendar-month]');
        const prevBtn = calendar.querySelector('[data-calendar-prev]');
        const nextBtn = calendar.querySelector('[data-calendar-next]');
        const gridContainer = calendar.querySelector('[data-calendar-grid]');
        const detailTitle = calendar.querySelector('[data-detail-title]');
        const detailList = calendar.querySelector('[data-detail-list]');
        const apiUrl = calendar.dataset.apiUrl;
        
        let currentDate = new Date();
        let events = [];
        let groupedEvents = {};
        let selectedDate = null;
        
        // Render calendar grid
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month label
            monthLabel.textContent = currentDate.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long'
            });
            
            // Calculate calendar days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const startDay = (firstDay + 6) % 7; // Convert Sunday=0 to Monday=0
            
            const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            let html = '';
            
            // Week day names
            weekDays.forEach(day => {
                html += `<div class="c-calendar-dashboard__day-name">${day}</div>`;
            });
            
            // Empty cells before first day
            for (let i = 0; i < startDay; i++) {
                html += '<div class="c-calendar-dashboard__day c-calendar-dashboard__day--empty"></div>';
            }
            
            // Days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateKey = formatDate(date);
                const eventsOnDay = groupedEvents[dateKey] || [];
                const isToday = date.toDateString() === today.toDateString();
                const isSelected = selectedDate === dateKey;
                const hasEvents = eventsOnDay.length > 0;
                
                let dayClasses = 'c-calendar-dashboard__day';
                if (isToday) dayClasses += ' c-calendar-dashboard__day--today';
                if (hasEvents) dayClasses += ' c-calendar-dashboard__day--has-events';
                if (isSelected) dayClasses += ' c-calendar-dashboard__day--selected';
                
                html += `
                    <button type="button" 
                            class="${dayClasses}" 
                            data-date="${dateKey}"
                            ${hasEvents ? '' : 'disabled'}>
                        <span class="c-calendar-dashboard__day-number">${day}</span>
                        ${hasEvents ? `<span class="c-calendar-dashboard__day-count">${eventsOnDay.length}</span>` : ''}
                    </button>
                `;
            }
            
            gridContainer.innerHTML = html;
            
            // Attach event listeners
            gridContainer.querySelectorAll('.c-calendar-dashboard__day--has-events').forEach(dayBtn => {
                dayBtn.addEventListener('click', () => {
                    selectedDate = dayBtn.dataset.date;
                    renderCalendar();
                    renderDetail(selectedDate);
                });
            });
        }
        
        // Render detail panel
        function renderDetail(dateKey) {
            if (!dateKey) {
                detailTitle.textContent = 'Detail';
                detailList.innerHTML = `
                    <div class="c-calendar-dashboard__placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        <p>Pilih tanggal untuk melihat detail.</p>
                    </div>
                `;
                return;
            }
            
            const eventsOnDay = groupedEvents[dateKey] || [];
            const date = new Date(dateKey);
            const dateLabel = date.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            
            detailTitle.textContent = dateLabel;
            
            if (eventsOnDay.length === 0) {
                detailList.innerHTML = `
                    <div class="c-calendar-dashboard__placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        <p>Tidak ada data pada tanggal ini.</p>
                    </div>
                `;
                return;
            }
            
            const eventsHtml = eventsOnDay.map(event => {
                return `
                    <div class="c-calendar-dashboard__event">
                        <h4 class="c-calendar-dashboard__event-title">${event.title || 'Event'}</h4>
                        <div class="c-calendar-dashboard__event-meta">
                            ${event.time ? `
                                <div class="c-calendar-dashboard__event-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    ${event.time}
                                </div>
                            ` : ''}
                            ${event.location ? `
                                <div class="c-calendar-dashboard__event-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                    ${event.location}
                                </div>
                            ` : ''}
                            ${event.description ? `
                                <div class="c-calendar-dashboard__event-meta-item">
                                    ${event.description}
                                </div>
                            ` : ''}
                        </div>
                        ${event.url ? `
                            <a href="${event.url}" class="c-calendar-dashboard__event-link">
                                Lihat Detail
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </a>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            detailList.innerHTML = eventsHtml;
        }
        
        // Load events from API
        async function loadEvents() {
            if (!apiUrl) {
                // Use static demo data if no API URL
                events = generateDemoEvents();
                groupedEvents = groupEventsByDate(events);
                renderCalendar();
                return;
            }
            
            try {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const start = new Date(year, month, 1);
                const end = new Date(year, month + 1, 0);
                
                const url = `${apiUrl}?start=${formatDate(start)}&end=${formatDate(end)}`;
                const response = await fetch(url);
                const data = await response.json();
                
                events = Array.isArray(data) ? data : [];
                groupedEvents = groupEventsByDate(events);
                renderCalendar();
            } catch (error) {
                console.error('Failed to load calendar events:', error);
                events = [];
                groupedEvents = {};
                renderCalendar();
            }
        }
        
        // Group events by date
        function groupEventsByDate(events) {
            return events.reduce((acc, event) => {
                const dateKey = event.date || (event.start ? event.start.substring(0, 10) : null);
                if (!dateKey) return acc;
                
                if (!acc[dateKey]) acc[dateKey] = [];
                acc[dateKey].push(event);
                
                return acc;
            }, {});
        }
        
        // Generate demo events
        function generateDemoEvents() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const demoEvents = [];
            
            // Add some demo events
            demoEvents.push({
                date: formatDate(new Date(year, month, 5)),
                title: 'Peminjaman Projector',
                time: '09:00 - 12:00',
                location: 'Ruang 301',
                url: '#'
            });
            
            demoEvents.push({
                date: formatDate(new Date(year, month, 5)),
                title: 'Peminjaman Laptop',
                time: '13:00 - 16:00',
                location: 'Lab Komputer',
                url: '#'
            });
            
            demoEvents.push({
                date: formatDate(new Date(year, month, 15)),
                title: 'Peminjaman Ruang Meeting',
                time: '10:00 - 14:00',
                location: 'Gedung A',
                url: '#'
            });
            
            demoEvents.push({
                date: formatDate(new Date(year, month, 20)),
                title: 'Peminjaman Alat Olahraga',
                time: '08:00 - 10:00',
                location: 'Lapangan',
                url: '#'
            });
            
            return demoEvents;
        }
        
        // Format date to YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Navigation
        prevBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            selectedDate = null;
            loadEvents();
        });
        
        nextBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            selectedDate = null;
            loadEvents();
        });
        
        // Initial load
        loadEvents();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initPasswordToggle();
    initFilePreview();
    initModal();
    initDropdown();
    initTabs();
    initAlert();
    initToast();
    initCalendarDashboard();
});
