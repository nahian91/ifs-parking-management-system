/**
 * IFS Parking Management System - Admin Core JavaScript
 * Author: Abdullah Nahian
 * Version: 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // লোকালাইজড ডেটা এবং ডিফল্ট কনফিগারেশন
        const rawCategories  = (typeof ifsParkingData !== 'undefined' && ifsParkingData.categories) ? ifsParkingData.categories : [];
        const currencySymbol = (typeof ifsParkingData !== 'undefined' && ifsParkingData.currency) ? ifsParkingData.currency : '৳';

        const vehLabels = {
            'Car': 'কার (Car)',
            'Noah': 'নোয়াহ (Noah)',
            'Hiace': 'হাইয়েস (Hiace)',
            'CNG': 'সিএনজি (CNG)',
            'Truck': 'ট্রাক (Truck)',
            'Bike': 'বাইক (Bike)',
            'Cycle': 'সাইকেল (Cycle)'
        };

        // ====================================================
        // ১. রিয়েল-টাইম বাংলাদেশ লাইভ ঘড়ি (Live Clock Widget)
        // ====================================================
        const timeEl = document.getElementById('ifs-live-time');
        const dateEl = document.getElementById('ifs-live-date');

        if (timeEl && dateEl) {
            const timeFormatter = new Intl.DateTimeFormat('en-US', {
                timeZone: 'Asia/Dhaka',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            const dateFormatter = new Intl.DateTimeFormat('bn-BD', {
                timeZone: 'Asia/Dhaka',
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            function updateBangladeshClock() {
                const now = new Date();
                timeEl.textContent = timeFormatter.format(now);
                dateEl.textContent = dateFormatter.format(now);
            }

            setInterval(updateBangladeshClock, 1000);
            updateBangladeshClock();
        }

        // ====================================================
        // ২. লাইভ কাউন্টার-আপ এবং অটো-রিলোড (Live Status Page)
        // ====================================================
        const liveCounters = document.querySelectorAll('.ifs-live-counter');
        if (liveCounters.length > 0) {
            function updateAllLiveCounters() {
                const now = new Date().getTime();
                liveCounters.forEach(counter => {
                    const entryTimeStr = counter.getAttribute('data-entry');
                    if (!entryTimeStr) return;

                    const entryTime = new Date(entryTimeStr).getTime();
                    let diffSeconds = Math.max(0, Math.floor((now - entryTime) / 1000));

                    const hours = Math.floor(diffSeconds / 3600);
                    diffSeconds %= 3600;
                    const minutes = Math.floor(diffSeconds / 60);
                    const seconds = diffSeconds % 60;

                    let durationStr = '';
                    if (hours > 0) durationStr += hours + ' ঘণ্টা ';
                    durationStr += minutes + ' মি: ' + seconds + ' সে:';

                    counter.textContent = durationStr;
                });
            }

            setInterval(updateAllLiveCounters, 1000);
            updateAllLiveCounters();
        }

        // লাইভ সার্চ ফিল্টারিং (লাইভ স্ট্যাটাস টেবিল)
        const searchInput = document.getElementById('ifs-search-input');
        const liveTable   = document.getElementById('ifs-live-table');
        let isUserSearching = false;

        if (searchInput && liveTable) {
            searchInput.addEventListener('input', function () {
                isUserSearching = this.value.trim().length > 0;
                const filter = this.value.toLowerCase();
                const rows = liveTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].id === 'no-records-row') continue;
                    const text = rows[i].textContent || rows[i].innerText;
                    rows[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? '' : 'none';
                }
            });
        }

        // ৬০ সেকেন্ড অটো-রিফ্রেশ টাইমার
        const timerEl = document.getElementById('ifs-refresh-timer');
        const releaseModal = document.getElementById('ifs-release-modal');

        if (timerEl) {
            let timeLeft = 60;
            setInterval(function () {
                if (isUserSearching || (releaseModal && releaseModal.classList.contains('show'))) {
                    timerEl.textContent = 'পজ';
                    return;
                }

                timeLeft--;
                timerEl.textContent = timeLeft + 's';

                if (timeLeft <= 0) {
                    window.location.reload();
                }
            }, 1000);
        }

        // রিলিজ বাটন ক্লিকে অটো ক্যালকুলেশন ও প্রিন্ট মডাল
        const releaseBtns = document.querySelectorAll('.ifs-trigger-release');
        if (releaseBtns.length > 0 && releaseModal) {
            const mVeh      = document.getElementById('m-veh');
            const mDeptCat  = document.getElementById('m-dept-cat');
            const mEntry    = document.getElementById('m-entry');
            const mExit     = document.getElementById('m-exit');
            const mDuration = document.getElementById('m-duration');
            const mTotalFee = document.getElementById('m-total-fee');
            const mDriver   = document.getElementById('m-driver');

            const formRelId   = document.getElementById('ifs-form-rel-id');
            const formFinalFee = document.getElementById('ifs-form-final-fee');

            releaseBtns.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();

                    const logId          = this.getAttribute('data-id');
                    const veh            = this.getAttribute('data-veh');
                    const dept           = this.getAttribute('data-dept');
                    const cat            = this.getAttribute('data-cat');
                    const driver         = this.getAttribute('data-driver');
                    const phone          = this.getAttribute('data-phone');
                    const entryIso       = this.getAttribute('data-entry');
                    const entryFormatted = this.getAttribute('data-entry-formatted');
                    const hourlyRate     = parseFloat(this.getAttribute('data-rate')) || 0;

                    const now       = new Date();
                    const entryDate = new Date(entryIso);
                    const diffMs    = Math.max(0, now.getTime() - entryDate.getTime());
                    const diffSec   = Math.floor(diffMs / 1000);

                    const totalHours   = Math.floor(diffSec / 3600);
                    const totalMinutes = Math.floor((diffSec % 3600) / 60);
                    const totalSeconds = diffSec % 60;

                    const billedHours        = Math.max(1, Math.ceil(diffSec / 3600));
                    const totalCalculatedFee = (billedHours * hourlyRate).toFixed(2);

                    if (mVeh) mVeh.textContent = veh;
                    if (mDeptCat) mDeptCat.textContent = dept + ' (' + cat + ')';
                    if (mEntry) mEntry.textContent = entryFormatted;

                    const exitFormatter = new Intl.DateTimeFormat('bn-BD', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                    if (mExit) mExit.textContent = exitFormatter.format(now);

                    if (mDuration) {
                        mDuration.textContent = (totalHours > 0 ? totalHours + ' ঘণ্টা ' : '') + totalMinutes + ' মিনিট ' + totalSeconds + ' সেকেন্ড';
                    }
                    if (mTotalFee) mTotalFee.textContent = currencySymbol + totalCalculatedFee;
                    if (mDriver) mDriver.textContent = driver + (phone ? ' (' + phone + ')' : '');

                    if (formRelId) formRelId.value = logId;
                    if (formFinalFee) formFinalFee.value = totalCalculatedFee;

                    releaseModal.classList.add('show');
                    setTimeout(function () {
                        window.print();
                    }, 350);
                });
            });

            const manualPrintBtn = document.getElementById('ifs-manual-print-btn');
            if (manualPrintBtn) {
                manualPrintBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            const closeModalBtn = document.getElementById('ifs-close-modal-btn');
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function () {
                    releaseModal.classList.remove('show');
                });
            }
        }

        // ====================================================
        // ৩. গাড়ি এন্ট্রি ও প্যাকেজ সিলেকশন (Vehicles Module)
        // ====================================================
        const deptRadios     = document.querySelectorAll('.ifs-dept-selector');
        const vehContainer   = document.getElementById('ifs-veh-types-container');
        const tabsNav        = document.getElementById('ifs-package-tabs-nav');
        const panesContainer = document.getElementById('ifs-package-panes-container');

        const hiddenCatId    = document.getElementById('ifs-hidden-cat-id');
        const hiddenRateType = document.getElementById('ifs-hidden-rate-type');

        const fDiv   = document.getElementById('f-div');
        const fChar  = document.getElementById('f-char');
        const fNum   = document.getElementById('f-num');
        const fName  = document.getElementById('f-name');
        const fPhone = document.getElementById('f-phone');

        const pVeh     = document.getElementById('p-veh');
        const pDept    = document.getElementById('p-dept');
        const pCatName = document.getElementById('p-catname');
        const pVehType = document.getElementById('p-vehtype');
        const pRate    = document.getElementById('p-rate');
        const pName    = document.getElementById('p-name');
        const pPhone   = document.getElementById('p-phone');

        function getSelectedDept() {
            const checked = document.querySelector('input[name="dept_radio"]:checked');
            return checked ? checked.value : '';
        }

        function renderVehicleTypes() {
            if (!vehContainer || !tabsNav || !panesContainer) return;

            const selectedDept = getSelectedDept();
            vehContainer.innerHTML = '';
            tabsNav.innerHTML = '';
            panesContainer.innerHTML = '';

            const filtered = rawCategories.filter(cat => cat.department === selectedDept);
            const uniqueVehTypes = [...new Set(filtered.map(cat => cat.vehicle_type || 'Car'))];

            if (uniqueVehTypes.length === 0) {
                vehContainer.innerHTML = '<span class="ifs-txt-err">কোনো গাড়ি কনফিগার করা নেই।</span>';
                updatePreview();
                return;
            }

            uniqueVehTypes.forEach((vType, idx) => {
                const labelText = vehLabels[vType] ? vehLabels[vType] : vType;
                const pill = document.createElement('label');
                pill.className = 'ifs-radio-pill';
                pill.innerHTML = `
                    <input type="radio" name="veh_type_radio" class="ifs-veh-selector" value="${vType}" ${idx === 0 ? 'checked' : ''}>
                    <span class="ifs-pill-label">
                        <svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                        ${labelText}
                    </span>
                `;
                vehContainer.appendChild(pill);
            });

            document.querySelectorAll('.ifs-veh-selector').forEach(radio => {
                radio.addEventListener('change', renderPackageTabs);
            });

            renderPackageTabs();
        }

        function renderPackageTabs() {
            if (!tabsNav || !panesContainer) return;

            const selectedDept = getSelectedDept();
            const selectedVehRadio = document.querySelector('input[name="veh_type_radio"]:checked');
            const selectedVeh = selectedVehRadio ? selectedVehRadio.value : '';

            tabsNav.innerHTML = '';
            panesContainer.innerHTML = '';

            const filtered = rawCategories.filter(cat => cat.department === selectedDept && (cat.vehicle_type || 'Car') === selectedVeh);

            if (filtered.length === 0) {
                panesContainer.innerHTML = '<span class="ifs-txt-err">কোনো প্যাকেজ/রেট পাওয়া যায়নি।</span>';
                updatePreview();
                return;
            }

            filtered.forEach((cat, idx) => {
                const tabId       = 'ifs-pkg-tab-' + cat.id;
                const displayName = cat.customer_type ? cat.customer_type : (cat.category_name ? cat.category_name : 'প্যাকেজ ' + (idx + 1));
                const hRate       = parseFloat(cat.hourly_rate).toFixed(2);
                const dRate       = parseFloat(cat.daily_rate).toFixed(2);
                const mRate       = parseFloat(cat.monthly_rate || 0).toFixed(2);
                const freeHours   = parseInt(cat.free_hours || 0, 10);

                const tabBtn = document.createElement('button');
                tabBtn.type = 'button';
                tabBtn.className = 'ifs-subtab-btn' + (idx === 0 ? ' active' : '');
                tabBtn.setAttribute('data-target', tabId);
                tabBtn.setAttribute('data-catid', cat.id);
                tabBtn.innerHTML = `
                    <svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    ${displayName}
                `;
                tabsNav.appendChild(tabBtn);

                const pane = document.createElement('div');
                pane.id = tabId;
                pane.className = 'ifs-tab-pane' + (idx === 0 ? ' active' : '');
                pane.innerHTML = `
                    <div class="ifs-step-box">
                        <div class="ifs-step-header">
                            <div>
                                <strong class="ifs-form-label-sm">${displayName} - বিলিং সাইকেল</strong>
                                <small class="ifs-rcpt-lbl">ফ্রি পার্কিং সুবিধা: <strong class="ifs-txt-exp">${freeHours > 0 ? freeHours + ' ঘণ্টা' : 'প্রযোজ্য নয়'}</strong></small>
                            </div>
                            <span class="ifs-pill-tag">৩টি কলাম থেকে ১টি বেছে নিন</span>
                        </div>

                        <div class="ifs-rate-3col-grid">
                            <label class="ifs-single-rate-radio-card">
                                <input type="radio" name="rate_choice_radio_${cat.id}" class="ifs-rate-option-input" 
                                       value="hourly" 
                                       data-catid="${cat.id}"
                                       data-dept="${cat.department}"
                                       data-catname="${displayName}"
                                       data-vehtype="${vehLabels[cat.vehicle_type] || cat.vehicle_type}"
                                       data-rate="${currencySymbol + hRate} / ঘণ্টা"
                                       ${idx === 0 ? 'checked' : ''}>
                                <div class="ifs-single-rate-box">
                                    <div class="ifs-rate-icon-wrapper ifs-rate-icon-blue">
                                        <svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                                    </div>
                                    <div>
                                        <small class="ifs-rate-lbl-sm">ঘণ্টা প্রতি</small>
                                        <strong class="ifs-rate-val-green">${currencySymbol + hRate}</strong>
                                    </div>
                                    <div class="ifs-check-circle">
                                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                                    </div>
                                </div>
                            </label>

                            <label class="ifs-single-rate-radio-card">
                                <input type="radio" name="rate_choice_radio_${cat.id}" class="ifs-rate-option-input" 
                                       value="daily" 
                                       data-catid="${cat.id}"
                                       data-dept="${cat.department}"
                                       data-catname="${displayName}"
                                       data-vehtype="${vehLabels[cat.vehicle_type] || cat.vehicle_type}"
                                       data-rate="${currencySymbol + dRate} / দিন">
                                <div class="ifs-single-rate-box">
                                    <div class="ifs-rate-icon-wrapper ifs-rate-icon-green">
                                        <svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                    </div>
                                    <div>
                                        <small class="ifs-rate-lbl-sm">দৈনিক ফি</small>
                                        <strong class="ifs-rate-val-blue">${currencySymbol + dRate}</strong>
                                    </div>
                                    <div class="ifs-check-circle">
                                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                                    </div>
                                </div>
                            </label>

                            <label class="ifs-single-rate-radio-card">
                                <input type="radio" name="rate_choice_radio_${cat.id}" class="ifs-rate-option-input" 
                                       value="monthly" 
                                       data-catid="${cat.id}"
                                       data-dept="${cat.department}"
                                       data-catname="${displayName}"
                                       data-vehtype="${vehLabels[cat.vehicle_type] || cat.vehicle_type}"
                                       data-rate="${currencySymbol + mRate} / মাস">
                                <div class="ifs-single-rate-box">
                                    <div class="ifs-rate-icon-wrapper ifs-rate-icon-purple">
                                        <svg class="ifs-svg-sm" viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V6c0-1.1-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                                    </div>
                                    <div>
                                        <small class="ifs-rate-lbl-sm">মাসিক ফি</small>
                                        <strong class="ifs-rate-val-slate">${currencySymbol + mRate}</strong>
                                    </div>
                                    <div class="ifs-check-circle">
                                        <svg viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                `;
                panesContainer.appendChild(pane);
            });

            document.querySelectorAll('.ifs-subtab-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.ifs-subtab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.ifs-tab-pane').forEach(p => p.classList.remove('active'));

                    this.classList.add('active');
                    const targetPane = document.getElementById(this.getAttribute('data-target'));
                    if (targetPane) {
                        targetPane.classList.add('active');
                        const checkedInPane = targetPane.querySelector('.ifs-rate-option-input:checked');
                        if (!checkedInPane) {
                            const firstRadio = targetPane.querySelector('.ifs-rate-option-input');
                            if (firstRadio) firstRadio.checked = true;
                        }
                        updatePreview();
                    }
                });
            });

            document.querySelectorAll('.ifs-rate-option-input').forEach(r => {
                r.addEventListener('change', updatePreview);
            });

            updatePreview();
        }

        function updatePreview() {
            if (!pVeh || !pName || !pPhone) return;

            const div  = fDiv && fDiv.value ? fDiv.value : '';
            const char = fChar && fChar.value ? ' ' + fChar.value : '';
            const num  = fNum && fNum.value ? ' ' + fNum.value : '';
            pVeh.innerText = (div || char || num) ? (div + char + num) : '-';

            pName.innerText  = fName && fName.value ? fName.value : '-';
            pPhone.innerText = fPhone && fPhone.value ? fPhone.value : '-';

            const activePane  = document.querySelector('.ifs-tab-pane.active');
            const checkedRate = activePane ? activePane.querySelector('.ifs-rate-option-input:checked') : null;

            if (checkedRate) {
                if (hiddenCatId) hiddenCatId.value = checkedRate.getAttribute('data-catid');
                if (hiddenRateType) hiddenRateType.value = checkedRate.value;

                if (pDept) pDept.innerText       = checkedRate.getAttribute('data-dept') || '-';
                if (pCatName) pCatName.innerText = checkedRate.getAttribute('data-catname') || '-';
                if (pVehType) pVehType.innerText = checkedRate.getAttribute('data-vehtype') || '-';
                if (pRate) pRate.innerText       = checkedRate.getAttribute('data-rate') || '-';
            } else {
                if (pDept) pDept.innerText       = '-';
                if (pCatName) pCatName.innerText = '-';
                if (pVehType) pVehType.innerText = '-';
                if (pRate) pRate.innerText       = '-';
            }
        }

        if (deptRadios.length > 0) {
            deptRadios.forEach(r => r.addEventListener('change', renderVehicleTypes));
        }

        if (fDiv) fDiv.addEventListener('input', updatePreview);
        if (fChar) fChar.addEventListener('input', updatePreview);
        if (fNum) fNum.addEventListener('input', updatePreview);
        if (fName) fName.addEventListener('input', updatePreview);
        if (fPhone) fPhone.addEventListener('input', updatePreview);

        renderVehicleTypes();

        // টেবিল থেকে তাৎক্ষণিক রসিদ প্রিন্ট
        const printBtns = document.querySelectorAll('.ifs-instant-print');
        if (printBtns.length > 0) {
            const tTime   = document.getElementById('t-time');
            const tVeh    = document.getElementById('t-veh');
            const tDept   = document.getElementById('t-dept');
            const tCat    = document.getElementById('t-cat');
            const tFee    = document.getElementById('t-fee');
            const tDriver = document.getElementById('t-driver');
            const tPhone  = document.getElementById('t-phone');

            printBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    if (tTime) tTime.textContent     = this.getAttribute('data-time') || '-';
                    if (tVeh) tVeh.textContent       = this.getAttribute('data-veh') || '-';
                    if (tDept) tDept.textContent     = this.getAttribute('data-dept') || '-';
                    if (tCat) tCat.textContent       = this.getAttribute('data-cat') || '-';
                    if (tFee) tFee.textContent       = this.getAttribute('data-fee') || '-';
                    if (tDriver) tDriver.textContent = this.getAttribute('data-driver') || '-';
                    if (tPhone) tPhone.textContent   = this.getAttribute('data-phone') || '-';

                    window.print();
                });
            });

            // অটো-প্রিন্ট ইউআরএল ট্র্যাকার
            const urlParams   = new URLSearchParams(window.location.search);
            const autoPrintId = urlParams.get('auto_print');
            if (autoPrintId && parseInt(autoPrintId, 10) > 0) {
                const targetBtn = document.querySelector(`.ifs-instant-print[data-id="${autoPrintId}"]`);
                if (targetBtn) {
                    targetBtn.click();
                }
            }
        }

        // ====================================================
        // ৪. ক্যাটাগরি রিপিটার রো হ্যান্ডলার (Categories Module)
        // ====================================================
        const addBtn = document.getElementById('ifs-add-row');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                const container = document.getElementById('ifs-repeater-container');
                const firstRow  = container ? container.querySelector('.ifs-repeater-row') : null;
                if (!firstRow) return;

                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll('input[type="number"]').forEach(input => input.value = '0.00');

                const freeHours = newRow.querySelector('input[name="free_hours[]"]');
                if (freeHours) freeHours.value = '0';

                const custType = newRow.querySelector('input[name="customer_type[]"]');
                if (custType) custType.value = 'General';

                container.appendChild(newRow);
            });
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.ifs-remove-row');
            if (btn) {
                const rows = document.querySelectorAll('.ifs-repeater-row');
                if (rows.length > 1) {
                    btn.closest('.ifs-repeater-row').remove();
                } else {
                    alert('কমপক্ষে একটি রো থাকা আবশ্যক।');
                }
            }
        });

        // ====================================================
        // ৫. মেম্বারশিপ মেয়াদ গণনা ও ফিল্টার (Membership Module)
        // ====================================================
        const planSelect      = document.getElementById('ifs-plan-select');
        const startDateInput  = document.getElementById('ifs-start-date');
        const expiryDateInput = document.getElementById('ifs-expiry-date');

        if (planSelect && startDateInput && expiryDateInput) {
            function addMonthsSafely(dateObj, monthsToAdd) {
                const currentDay = dateObj.getDate();
                dateObj.setDate(1);
                dateObj.setMonth(dateObj.getMonth() + monthsToAdd);

                const maxDaysInNewMonth = new Date(dateObj.getFullYear(), dateObj.getMonth() + 1, 0).getDate();
                dateObj.setDate(Math.min(currentDay, maxDaysInNewMonth));
                return dateObj;
            }

            function calculateExpiry() {
                const startVal = startDateInput.value;
                if (!startVal) return;

                const [year, month, day] = startVal.split('-').map(Number);
                let targetDate = new Date(year, month - 1, day);
                const planVal = planSelect.value;

                if (planVal.includes('১ মাস') || planVal.includes('1-month')) {
                    targetDate = addMonthsSafely(targetDate, 1);
                } else if (planVal.includes('৩ মাস') || planVal.includes('3-month')) {
                    targetDate = addMonthsSafely(targetDate, 3);
                } else if (planVal.includes('৬ মাস') || planVal.includes('6-month')) {
                    targetDate = addMonthsSafely(targetDate, 6);
                } else if (planVal.includes('১ বছর') || planVal.includes('1-year')) {
                    targetDate.setFullYear(targetDate.getFullYear() + 1);
                }

                const yyyy = targetDate.getFullYear();
                const mm   = String(targetDate.getMonth() + 1).padStart(2, '0');
                const dd   = String(targetDate.getDate()).padStart(2, '0');

                expiryDateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            planSelect.addEventListener('change', calculateExpiry);
            startDateInput.addEventListener('change', calculateExpiry);
        }

        // ====================================================
        // ৬. ওয়ার্ডপ্রেস মিডিয়া আপলোডার (Staff & Settings)
        // ====================================================
        // স্টাফ ছবি
        const uploadStaffBtn = $('#ifs_upload_staff_img');
        if (uploadStaffBtn.length) {
            uploadStaffBtn.on('click', function (e) {
                e.preventDefault();
                const imgUploader = wp.media({
                    title: 'স্টাফ প্রোফাইল ছবি নির্বাচন করুন',
                    button: { text: 'ব্যবহার করুন' },
                    multiple: false
                }).on('select', function () {
                    const attachment = imgUploader.state().get('selection').first().toJSON();
                    $('#ifs_staff_img_url').val(attachment.url);
                    $('#ifs_staff_img_preview').html('<img src="' + attachment.url + '" class="ifs-staff-avatar-preview" alt="Preview" />');
                }).open();
            });
        }

        // কোম্পানি লোগো (সাইডবার কি এর সাথে সমন্বিত)
        const uploadLogoBtn = $('#ifs_upload_logo_btn');
        if (uploadLogoBtn.length) {
            uploadLogoBtn.on('click', function (e) {
                e.preventDefault();
                const logoUploader = wp.media({
                    title: 'কোম্পানি লোগো নির্বাচন করুন',
                    button: { text: 'লোগো হিসেবে ব্যবহার করুন' },
                    multiple: false
                }).on('select', function () {
                    const attachment = logoUploader.state().get('selection').first().toJSON();
                    $('#ifs_parking_logo_url').val(attachment.url);
                    $('#ifs_logo_preview').html('<div class="ifs-logo-preview-card"><img src="' + attachment.url + '" class="ifs-brand-logo-preview" alt="Site Logo" /></div>');
                }).open();
            });
        }
    });
})(jQuery);