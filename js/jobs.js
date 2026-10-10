/**
 * ITINFO ACADEMY - Job Board
 * Loads listings from backend/api/jobs.php and renders the filter UI.
 * All data from the API is inserted with textContent (never innerHTML).
 */
(function () {
    'use strict';

    var API_URL = 'backend/api/jobs.php';

    var state = {
        q: '',
        city: '',
        category: '',
        type: '',
        contract: '',
        company: '',
        salary_min: 0,
        remote: 0,
        days: 0,
        sort: 'newest',
        page: 1,
        per_page: 12
    };

    var els = {};
    var lastTotal = 0;
    var lastTotalPages = 1;

    /* ------------------------------------------------------------------ */
    /* helpers                                                           */
    /* ------------------------------------------------------------------ */

    function $(id) { return document.getElementById(id); }

    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    var ENTITIES = {
        '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#39;': "'",
        '&hellip;': '...', '&nbsp;': ' ', '&rsquo;': "'", '&ldquo;': '"',
        '&rdquo;': '"', '&mdash;': '-', '&ndash;': '-'
    };

    /** Adzuna descriptions are HTML - strip tags/entities for safe text use. */
    function stripTags(html) {
        if (!html) { return ''; }
        var text = String(html);

        // turn block level tags into spaces so words don't merge
        text = text.replace(/<\/(p|div|li|ul|br|h[1-6]|tr)>/gi, ' ');

        var tmp = document.createElement('textarea');
        tmp.innerHTML = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');

        var out = tmp.value;

        Object.keys(ENTITIES).forEach(function (entity) {
            out = out.split(entity).join(ENTITIES[entity]);
        });

        return out.replace(/\s+/g, ' ').trim();
    }

    function formatSalary(job) {
        if (!job.salary_min && !job.salary_max) { return null; }

        var fmt = function (n) { return (n / 100000).toFixed(1).replace(/\.0$/, '') + ' LPA'; };
        var label;

        if (job.salary_min && job.salary_max) {
            label = fmt(job.salary_min) + ' - ' + fmt(job.salary_max);
        } else if (job.salary_min) {
            label = fmt(job.salary_min) + '+';
        } else {
            label = 'Up to ' + fmt(job.salary_max);
        }

        return job.salary_is_predicted ? label + ' (est.)' : label;
    }

    function daysAgo(dateString) {
        if (!dateString) { return ''; }
        var posted = new Date(dateString + 'T00:00:00');
        if (isNaN(posted.getTime())) { return ''; }

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var diff = Math.floor((today - posted) / 86400000);
        if (diff <= 0) { return 'Posted today'; }
        if (diff === 1) { return 'Posted yesterday'; }
        if (diff < 30) { return 'Posted ' + diff + ' days ago'; }
        return 'Posted ' + Math.floor(diff / 30) + ' month(s) ago';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function prettyLabel(value) {
        if (!value) { return ''; }
        return value.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    /* ------------------------------------------------------------------ */
    /* state <-> URL                                                     */
    /* ------------------------------------------------------------------ */

    function readQuery() {
        var params = new URLSearchParams(window.location.search);

        state.q = (params.get('q') || '').slice(0, 80);
        state.city = params.get('city') || '';
        state.category = params.get('category') || '';
        state.type = params.get('type') || '';
        state.contract = params.get('contract') || '';
        state.company = params.get('company') || '';
        state.salary_min = parseInt(params.get('salary_min') || '0', 10) || 0;
        state.remote = params.get('remote') === '1' ? 1 : 0;
        state.days = parseInt(params.get('days') || '0', 10) || 0;
        state.sort = params.get('sort') || 'newest';
        state.page = parseInt(params.get('page') || '1', 10) || 1;
    }

    function writeQuery() {
        var params = new URLSearchParams();

        if (state.q) { params.set('q', state.q); }
        if (state.city) { params.set('city', state.city); }
        if (state.category) { params.set('category', state.category); }
        if (state.type) { params.set('type', state.type); }
        if (state.contract) { params.set('contract', state.contract); }
        if (state.company) { params.set('company', state.company); }
        if (state.salary_min) { params.set('salary_min', state.salary_min); }
        if (state.remote) { params.set('remote', '1'); }
        if (state.days) { params.set('days', state.days); }
        if (state.sort && state.sort !== 'newest') { params.set('sort', state.sort); }
        if (state.page > 1) { params.set('page', state.page); }

        var qs = params.toString();
        var url = window.location.pathname + (qs ? '?' + qs : '');
        window.history.replaceState(null, '', url);
    }

    function syncInputs() {
        $('jobSearch').value = state.q;
        $('jobCompany').value = state.company;
        $('jobCity').value = state.city;
        $('jobCategory').value = state.category;
        $('jobType').value = state.type;
        $('jobContract').value = state.contract;
        $('jobDays').value = String(state.days);
        $('jobSalary').value = String(state.salary_min);
        $('jobRemote').checked = state.remote === 1;
        $('jobSort').value = state.sort;
    }

    /* ------------------------------------------------------------------ */
    /* rendering                                                         */
    /* ------------------------------------------------------------------ */

    function renderSkeleton() {
        var grid = $('jobsGrid');
        if (!grid) { return; }
        grid.innerHTML = '';
        grid.className = 'jobs-skeleton';

        for (var i = 0; i < 6; i++) {
            var card = document.createElement('div');
            card.className = 'jobs-skeleton-card';
            grid.appendChild(card);
        }
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = text; }
        return node;
    }

    function renderCard(job) {
        var card = el('article', 'job-card');

        var top = el('div', 'job-card-top');

        var headingWrap = el('div');
        headingWrap.appendChild(el('h3', null, job.title || 'Untitled role'));

        var company = el('div', 'job-card-company');
        company.appendChild(el('i', 'fas fa-building', null));
        company.appendChild(el('span', null, job.company || 'Company not listed'));
        headingWrap.appendChild(company);

        top.appendChild(headingWrap);
        card.appendChild(top);

        // badges
        var badges = el('div', 'job-badge-group');

        if (job.contract_time) {
            badges.appendChild(el('span', 'job-badge job-badge-type', prettyLabel(job.contract_time)));
        }
        if (job.contract_type) {
            badges.appendChild(el('span', 'job-badge', prettyLabel(job.contract_type)));
        }
        if (job.is_remote) {
            badges.appendChild(el('span', 'job-badge job-badge-remote', 'Remote'));
        }

        var salary = formatSalary(job);
        if (salary) {
            badges.appendChild(el('span', 'job-badge job-badge-salary', salary));
        }

        if (badges.children.length) { card.appendChild(badges); }

        // meta line
        var meta = el('div', 'job-card-meta');
        meta.appendChild(el('span', null, (job.city || job.location || 'Location not specified')));
        if (job.state) { meta.appendChild(el('span', null, job.state)); }
        if (job.category) { meta.appendChild(el('span', null, job.category)); }
        card.appendChild(meta);

        // excerpt
        var excerpt = stripTags(job.description).slice(0, 180);
        if (excerpt) {
            card.appendChild(el('p', 'job-card-excerpt', excerpt));
        }

        // footer
        var footer = el('div', 'job-card-footer');
        footer.appendChild(el('span', 'job-posted', daysAgo(job.posted_date)));

        if (job.redirect_url) {
            var link = el('a', 'job-btn job-btn-primary', 'View & Apply');
            link.href = job.redirect_url;
            link.target = '_blank';
            link.rel = 'noopener nofollow';
            footer.appendChild(link);
        }

        card.appendChild(footer);
        return card;
    }

    function renderChips() {
        var wrap = $('jobsActiveFilters');
        if (!wrap) { return; }
        wrap.innerHTML = '';

        var chips = [
            { key: 'q', label: '"' + state.q + '"' },
            { key: 'city', label: state.city },
            { key: 'category', label: state.category },
            { key: 'type', label: prettyLabel(state.type) },
            { key: 'contract', label: prettyLabel(state.contract) },
            { key: 'salary_min', label: state.salary_min + '+ LPA' },
            { key: 'remote', label: 'Remote only' },
            { key: 'days', label: 'Last ' + state.days + ' days' }
        ];

        chips.forEach(function (chip) {
            if (!chip.label) { return; }

            var node = el('span', 'jobs-chip');
            node.appendChild(document.createTextNode(chip.label));

            var remove = el('button', null, '\u00D7');
            remove.setAttribute('aria-label', 'Remove filter');
            remove.addEventListener('click', function () {
                clearFilter(chip.key);
            });

            node.appendChild(remove);
            wrap.appendChild(node);
        });
    }

    function clearFilter(key) {
        if (key === 'q') {
            state.q = '';
            $('jobSearch').value = '';
        } else if (key === 'company') {
            state.company = '';
            $('jobCompany').value = '';
        } else if (key === 'salary_min') {
            state.salary_min = 0;
            $('jobSalary').value = '0';
        } else if (key === 'remote') {
            state.remote = 0;
            $('jobRemote').checked = false;
        } else {
            state[key] = '';
            if ($('job' + key.charAt(0).toUpperCase() + key.slice(1))) {
                $('job' + key.charAt(0).toUpperCase() + key.slice(1)).value = '';
            }
        }
        state.page = 1;
        load();
    }

    function renderPagination() {
        var wrap = $('jobsPagination');
        if (!wrap) { return; }
        wrap.innerHTML = '';

        if (lastTotalPages <= 1) { return; }

        var build = function (label, page, options) {
            options = options || {};
            var button = el('button', options.className || '', label);

            if (options.active) {
                button.classList.add('is-active');
            }
            if (options.disabled) {
                button.disabled = true;
            }

            button.addEventListener('click', function () {
                state.page = page;
                load();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            return button;
        };

        wrap.appendChild(build('\u2039 Prev', Math.max(1, state.page - 1), { disabled: state.page === 1 }));

        var pages = [];
        var total = lastTotalPages;
        var current = state.page;

        for (var i = 1; i <= total; i++) {
            if (
                i === 1 || i === total ||
                (i >= current - 1 && i <= current + 1) ||
                (current <= 3 && i <= 5) ||
                (current >= total - 2 && i >= total - 4)
            ) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }

        pages.forEach(function (page) {
            if (page === '...') {
                wrap.appendChild(el('span', 'is-ellipsis', '\u2026'));
            } else {
                wrap.appendChild(build(String(page), page, { active: page === current }));
            }
        });

        wrap.appendChild(build('Next \u203A', Math.min(total, state.page + 1), { disabled: state.page === total }));
    }

    function renderFacets(facets) {
        if (!facets) { return; }

        var citySelect = $('jobCity');
        if (citySelect) {
            var currentCity = state.city;
            citySelect.innerHTML = '<option value="">All cities</option>';
            (facets.cities || []).forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.value + ' (' + item.total + ')';
                if (item.value === currentCity) { option.selected = true; }
                citySelect.appendChild(option);
            });
        }

        var categorySelect = $('jobCategory');
        if (categorySelect) {
            var currentCategory = state.category;
            categorySelect.innerHTML = '<option value="">All IT categories</option>';
            (facets.categories || []).forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = (item.label || item.value) + ' (' + item.total + ')';
                if (item.value === currentCategory) { option.selected = true; }
                categorySelect.appendChild(option);
            });
        }

        var typeSelect = $('jobType');
        if (typeSelect) {
            var currentType = state.type;
            typeSelect.innerHTML = '<option value="">Any type</option>';
            (facets.types || []).forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = prettyLabel(item.value) + ' (' + item.total + ')';
                if (item.value === currentType) { option.selected = true; }
                typeSelect.appendChild(option);
            });
        }

        var contractSelect = $('jobContract');
        if (contractSelect) {
            var currentContract = state.contract;
            contractSelect.innerHTML = '<option value="">Any contract</option>';
            (facets.contracts || []).forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = prettyLabel(item.value) + ' (' + item.total + ')';
                if (item.value === currentContract) { option.selected = true; }
                contractSelect.appendChild(option);
            });
        }
    }

    function renderHeroStats(facets) {
        var wrap = $('jobsHeroStats');
        if (!wrap) { return; }

        var total = lastTotal;
        var cities = facets && facets.cities ? facets.cities.length : 0;
        var remote = 0;

        var stats = [
            { label: total ? (total === 1 ? '1 live job' : total + ' live jobs') : 'Loading jobs', icon: 'fa-briefcase' },
            { label: cities ? cities + '+ cities' : 'Across India', icon: 'fa-location-dot' },
            { label: 'Updated daily', icon: 'fa-rotate' }
        ];

        wrap.innerHTML = '';
        stats.forEach(function (stat) {
            var node = el('div', 'jobs-hero-stat');
            node.appendChild(el('i', 'fas ' + stat.icon, null));
            node.appendChild(el('span', null, stat.label));
            wrap.appendChild(node);
        });
    }

    /* ------------------------------------------------------------------ */
    /* data loading                                                      */
    /* ------------------------------------------------------------------ */

    function load() {
        renderChips();
        writeQuery();
        renderSkeleton();
        renderPagination();

        var countEl = $('jobsCount');
        if (countEl) { countEl.textContent = 'Loading'; }

        var params = new URLSearchParams();
        Object.keys(state).forEach(function (key) {
            var value = state[key];
            if (value !== '' && value !== 0 && value !== null && value !== undefined) {
                params.set(key, value);
            }
        });

        fetch(API_URL + '?' + params.toString(), {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                var grid = $('jobsGrid');
                var stateBox = $('jobsState');
                if (!grid) { return; }

                if (!payload || !payload.success) {
                    grid.className = 'jobs-grid';
                    grid.innerHTML = '';
                    var message = (payload && payload.message) || 'Could not load jobs right now.';
                    if (stateBox) {
                        stateBox.innerHTML = '';
                        stateBox.style.display = 'block';
                        stateBox.appendChild(el('i', 'fas fa-triangle-exclamation', null));
                        stateBox.appendChild(el('h3', null, 'Unable to load jobs'));
                        stateBox.appendChild(el('p', null, message));
                    }
                    if (countEl) { countEl.textContent = 'Unavailable'; }
                    return;
                }

                lastTotal = payload.total || 0;
                lastTotalPages = payload.total_pages || 1;

                if (payload.page && payload.page !== state.page) {
                    state.page = payload.page;
                    writeQuery();
                }

                renderFacets(payload.facets);
                renderHeroStats(payload.facets);
                renderChips();
                renderPagination();

                if (countEl) {
                    countEl.textContent = String(payload.total);
                }

                var from = lastTotal === 0 ? 0 : (state.page - 1) * state.per_page + 1;
                var to = Math.min(state.page * state.per_page, lastTotal);
                var rangeEl = $('jobsRange');
                if (rangeEl) {
                    rangeEl.textContent = lastTotal === 0 ? '' : 'Showing ' + from + '\u2013' + to + ' of ' + lastTotal;
                }

                grid.className = 'jobs-grid';
                grid.innerHTML = '';

                if (!payload.data || !payload.data.length) {
                    if (stateBox) {
                        stateBox.innerHTML = '';
                        stateBox.style.display = 'block';
                        stateBox.appendChild(el('i', 'fas fa-magnifying-glass', null));
                        stateBox.appendChild(el('h3', null, 'No matching jobs'));
                        stateBox.appendChild(el('p', null,
                            'Try removing a filter or searching for a different skill, city or job title.'));
                    }
                    return;
                }

                if (stateBox) {
                    stateBox.style.display = 'none';
                    stateBox.innerHTML = '';
                }

                payload.data.forEach(function (job) {
                    grid.appendChild(renderCard(job));
                });
            })
            .catch(function () {
                var grid = $('jobsGrid');
                var stateBox = $('jobsState');
                if (grid) { grid.className = 'jobs-grid'; grid.innerHTML = ''; }
                if (stateBox) {
                    stateBox.innerHTML = '';
                    stateBox.style.display = 'block';
                    stateBox.appendChild(el('i', 'fas fa-wifi', null));
                    stateBox.appendChild(el('h3', null, 'Connection problem'));
                    stateBox.appendChild(el('p', null, 'Please check your internet connection and try again.'));
                }
                if ($('jobsCount')) { $('jobsCount').textContent = 'Error'; }
            });
    }

    /* ------------------------------------------------------------------ */
    /* events                                                            */
    /* ------------------------------------------------------------------ */

    function bindEvents() {
        var searchInput = $('jobSearch');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function () {
                state.q = searchInput.value.trim();
                state.page = 1;
                load();
            }, 400));
        }

        var companyInput = $('jobCompany');
        if (companyInput) {
            companyInput.addEventListener('input', debounce(function () {
                state.company = companyInput.value.trim();
                state.page = 1;
                load();
            }, 400));
        }

        var bindChange = function (id, key, transform) {
            var field = $(id);
            if (!field) { return; }
            field.addEventListener('change', function () {
                var value = field.value;
                state[key] = transform ? transform(value) : value;
                state.page = 1;
                load();
            });
        };

        bindChange('jobCity', 'city');
        bindChange('jobCategory', 'category');
        bindChange('jobType', 'type');
        bindChange('jobContract', 'contract');
        bindChange('jobDays', 'days', function (v) { return parseInt(v, 10) || 0; });
        bindChange('jobSalary', 'salary_min', function (v) { return parseInt(v, 10) || 0; });
        bindChange('jobSort', 'sort', function (v) { return v || 'newest'; });

        var remote = $('jobRemote');
        if (remote) {
            remote.addEventListener('change', function () {
                state.remote = remote.checked ? 1 : 0;
                state.page = 1;
                load();
            });
        }

        var reset = $('jobReset');
        if (reset) {
            reset.addEventListener('click', function () {
                state = {
                    q: '', city: '', category: '', type: '', contract: '', company: '',
                    salary_min: 0, remote: 0, days: 0, sort: 'newest',
                    page: 1, per_page: state.per_page
                };
                syncInputs();
                load();
            });
        }

        var mobileToggle = $('jobsFilterToggle');
        var filters = $('jobsFilters');
        if (mobileToggle && filters) {
            mobileToggle.addEventListener('click', function () {
                filters.classList.toggle('is-open');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        els = {
            grid: $('jobsGrid')
        };
        if (!els.grid) { return; }

        readQuery();
        syncInputs();
        bindEvents();
        load();
    });
})();
