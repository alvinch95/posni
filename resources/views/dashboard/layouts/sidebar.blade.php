{{--
  Sidebar layout: brand (fixed) / nav (scrolls on its own) / logout (pinned).
  Each group knows its own URLs ($navOpen): the group that holds the current page is
  expanded on load and its header is marked .has-active. Permissions are unchanged:
  every @can('admin') gate is where it always was.
--}}
@php
  // Sale pages: the cashier (/dashboard/sales, /create) belongs to Transaction. The order detail
  // (/dashboard/sales/{id}, only reachable from Sales History) belongs to Report > Sales History.
  $onSalesPage    = Request::is('dashboard/sales', 'dashboard/sales/create*');
  $onSalesHistory = Request::is('dashboard/sales/history*') || Request::routeIs('sales.show', 'sales.edit');

  $navOpen = [
    'attendance'  => Request::is('attendance/*'),
    'masterData'  => Request::is('dashboard/series*', 'dashboard/suppliers*', 'dashboard/customers*', 'dashboard/items*', 'dashboard/hampers*'),
    'transaction' => Request::is('dashboard/purchases*') || $onSalesPage,
    'stock'       => Request::is('dashboard/stock*'),
    'cash'        => Request::is('dashboard/cashbalances*'),
    'report'      => $onSalesHistory || Request::is('dashboard/shopeereminder*'),
    'website'     => Request::is('dashboard/website*'),
  ];
@endphp
<nav id="sidebarMenu" class="sidebar collapse" aria-label="Main navigation">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="/">POSNI</a>
        <button type="button" class="sidebar-mini-toggle" id="sidebarMiniToggle" aria-label="Collapse sidebar" aria-expanded="true" aria-controls="sidebarScroll">
            <span data-feather="chevrons-left"></span>
        </button>
    </div>

    <div class="sidebar-scroll" id="sidebarScroll">
      @can('admin')
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" aria-current="{{ Request::is('dashboard') ? 'page' : 'false' }}" href="/dashboard" data-title="Dashboard">
            <span data-feather="home"></span>
            <span class="sidebar-label">Dashboard</span>
          </a>
        </li>
      </ul>
      @endcan

      <div class="sidebar-heading {{ $navOpen['attendance'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['attendance'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#attendanceSubmenu" aria-expanded="{{ $navOpen['attendance'] ? 'true' : 'false' }}" aria-controls="attendanceSubmenu" data-title="Absensi">
          <span data-feather="clock"></span>
          <span class="sidebar-label">Absensi</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="attendanceSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['attendance'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('attendance/checkin*') ? 'active' : '' }}" href="/attendance/checkin">
            <span data-feather="user-check"></span>
            <span class="sidebar-label">Checkin/Checkout</span>
          </a>
        </li>
        @can('admin')
        <li class="nav-item">
            <a class="nav-link {{ Request::is('attendance/record*') ? 'active' : '' }}" href="{{ route('attendance.records') }}">
                <span data-feather="file-text"></span>
                <span class="sidebar-label">Attendance Records</span>
            </a>
        </li>
        @endcan
      </ul>

      @can('admin')
      <div class="sidebar-heading {{ $navOpen['masterData'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['masterData'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#masterDataSubmenu" aria-expanded="{{ $navOpen['masterData'] ? 'true' : 'false' }}" aria-controls="masterDataSubmenu" data-title="Master Data">
          <span data-feather="database"></span>
          <span class="sidebar-label">Master Data</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="masterDataSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['masterData'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/series*') ? 'active' : '' }}" href="/dashboard/series">
            <span data-feather="grid"></span>
            <span class="sidebar-label">Series</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/suppliers*') ? 'active' : '' }}" href="/dashboard/suppliers">
            <span data-feather="package"></span>
            <span class="sidebar-label">Suppliers</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/customers*') ? 'active' : '' }}" href="/dashboard/customers">
            <span data-feather="users"></span>
            <span class="sidebar-label">Customers</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/items*') ? 'active' : '' }}" href="/dashboard/items">
            <span data-feather="box"></span>
            <span class="sidebar-label">Items</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/hampers*') ? 'active' : '' }}" href="/dashboard/hampers">
            <span data-feather="gift"></span>
            <span class="sidebar-label">Hampers</span>
          </a>
        </li>
      </ul>
      @endcan

      @can('admin')
      <div class="sidebar-heading {{ $navOpen['transaction'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['transaction'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#transactionSubmenu" aria-expanded="{{ $navOpen['transaction'] ? 'true' : 'false' }}" aria-controls="transactionSubmenu" data-title="Transaction">
          <span data-feather="shopping-cart"></span>
          <span class="sidebar-label">Transaction</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="transactionSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['transaction'] ? 'show' : '' }}">
        @can('admin')
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/purchases*') ? 'active' : '' }}" href="/dashboard/purchases">
            <span data-feather="shopping-cart"></span>
            <span class="sidebar-label">Purchase</span>
          </a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link {{ $onSalesPage ? 'active' : '' }}" href="/dashboard/sales">
            <span data-feather="dollar-sign"></span>
            <span class="sidebar-label">Sales</span>
          </a>
        </li>
      </ul>
      @endcan

      @can('admin')
      <div class="sidebar-heading {{ $navOpen['stock'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['stock'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#stockOpnameSubmenu" aria-expanded="{{ $navOpen['stock'] ? 'true' : 'false' }}" aria-controls="stockOpnameSubmenu" data-title="Stock Opname">
          <span data-feather="package"></span>
          <span class="sidebar-label">Stock Opname</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="stockOpnameSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['stock'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockopname*') ? 'active' : '' }}" href="/dashboard/stockopname">
            <span data-feather="database"></span>
            <span class="sidebar-label">Stock Opname</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockin*') ? 'active' : '' }}" href="/dashboard/stockin">
            <span data-feather="plus"></span>
            <span class="sidebar-label">Stock In History</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockout*') ? 'active' : '' }}" href="/dashboard/stockout">
            <span data-feather="minus"></span>
            <span class="sidebar-label">Stock Out History</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-heading {{ $navOpen['cash'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['cash'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#cashBalanceSubmenu" aria-expanded="{{ $navOpen['cash'] ? 'true' : 'false' }}" aria-controls="cashBalanceSubmenu" data-title="Cash Balance">
          <span data-feather="dollar-sign"></span>
          <span class="sidebar-label">Cash Balance</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="cashBalanceSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['cash'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/cashbalances/create*') ? 'active' : '' }}" href="/dashboard/cashbalances/create">
            <span data-feather="check-circle"></span>
            <span class="sidebar-label">New Transaction</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/cashbalances*') && ! Request::is('dashboard/cashbalances/create*') ? 'active' : '' }}" href="/dashboard/cashbalances">
            <span data-feather="book"></span>
            <span class="sidebar-label">Cash History</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-heading {{ $navOpen['report'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['report'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#reportSubmenu" aria-expanded="{{ $navOpen['report'] ? 'true' : 'false' }}" aria-controls="reportSubmenu" data-title="Report">
          <span data-feather="bar-chart-2"></span>
          <span class="sidebar-label">Report</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="reportSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['report'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ $onSalesHistory ? 'active' : '' }}" href="/dashboard/sales/history">
            <span data-feather="clipboard"></span>
            <span class="sidebar-label">Sales History</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/shopeereminder*') ? 'active' : '' }}" href="/dashboard/shopeereminder">
            <span data-feather="check-square"></span>
            <span class="sidebar-label">Shopee Reminder</span>
          </a>
        </li>
      </ul>
      @endcan

      @can('admin')
      <div class="sidebar-heading {{ $navOpen['website'] ? 'is-open has-active' : '' }}">
        <button type="button" class="sidebar-group-toggle {{ $navOpen['website'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#websiteSubmenu" aria-expanded="{{ $navOpen['website'] ? 'true' : 'false' }}" aria-controls="websiteSubmenu" data-title="Website">
          <span data-feather="globe"></span>
          <span class="sidebar-label">Website</span>
          <span class="sidebar-chevron" data-feather="chevron-right"></span>
        </button>
      </div>
      <ul id="websiteSubmenu" class="nav flex-column sidebar-submenu collapse {{ $navOpen['website'] ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/products*') ? 'active' : '' }}" href="/dashboard/website/products">
            <span data-feather="shopping-bag"></span>
            <span class="sidebar-label">Produk Website</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/categories*') ? 'active' : '' }}" href="/dashboard/website/categories">
            <span data-feather="tag"></span>
            <span class="sidebar-label">Kategori</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/testimonials*') ? 'active' : '' }}" href="/dashboard/website/testimonials">
            <span data-feather="message-circle"></span>
            <span class="sidebar-label">Testimoni</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/content*') ? 'active' : '' }}" href="/dashboard/website/content">
            <span data-feather="edit-3"></span>
            <span class="sidebar-label">Konten Homepage</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/faqs*') ? 'active' : '' }}" href="/dashboard/website/faqs">
            <span data-feather="help-circle"></span>
            <span class="sidebar-label">FAQ</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/website/contact*') ? 'active' : '' }}" href="/dashboard/website/contact">
            <span data-feather="link"></span>
            <span class="sidebar-label">Link &amp; Kontak</span>
          </a>
        </li>
      </ul>
      @endcan
    </div>

    <div class="sidebar-footer">
      <form action="/logout" method="POST">
        @csrf
        <button type="submit" class="sidebar-logout" data-title="Logout">
          <span data-feather="log-out"></span>
          <span class="sidebar-label">Logout</span>
        </button>
      </form>
    </div>
</nav>

<script>
  /*
   * Sidebar behaviour (one generic script, no per-group code):
   *  1. Any collapse group toggles .is-open on its header, so CSS rotates the chevron.
   *  2. Desktop (>= 768px) mini mode: icons only, remembered in localStorage.
   *     The same class is also applied from <head> in main.blade.php to avoid a flash.
   *  3. Mini mode never applies on mobile; there the header toggler opens #sidebarMenu off-canvas.
   */
  (function () {
    'use strict';

    var STORAGE_KEY = 'posni.sidebar.mini';
    var sidebar = document.getElementById('sidebarMenu');
    if (!sidebar) { return; }

    var root = document.documentElement;
    var scrollArea = document.getElementById('sidebarScroll');
    var miniToggle = document.getElementById('sidebarMiniToggle');
    var desktop = window.matchMedia('(min-width: 768px)');

    function isMini() {
      return desktop.matches && root.classList.contains('sidebar-mini');
    }

    function remember(mini) {
      try { localStorage.setItem(STORAGE_KEY, mini ? '1' : '0'); } catch (e) { /* storage blocked: state just won't persist */ }
    }

    // Hover tooltips for the icon-only state; removed again when the labels are visible.
    function syncMiniUi() {
      var mini = isMini();
      var items = sidebar.querySelectorAll('[data-title]');
      for (var i = 0; i < items.length; i++) {
        if (mini) { items[i].setAttribute('title', items[i].getAttribute('data-title')); }
        else { items[i].removeAttribute('title'); }
      }
      if (miniToggle) {
        var label = mini ? 'Expand sidebar' : 'Collapse sidebar';
        miniToggle.setAttribute('aria-label', label);
        miniToggle.setAttribute('aria-expanded', mini ? 'false' : 'true');
        miniToggle.setAttribute('title', label);
      }
    }

    // Keep the current page's link inside the scrolling nav.
    function revealActive() {
      var active = scrollArea && scrollArea.querySelector('.nav-link.active');
      if (!active || !active.offsetParent) { return; }
      var a = active.getBoundingClientRect();
      var s = scrollArea.getBoundingClientRect();
      if (a.top < s.top || a.bottom > s.bottom) {
        scrollArea.scrollTop += (a.top - s.top) - (s.height - a.height) / 2;
      }
    }

    function setMini(mini) {
      root.classList.toggle('sidebar-mini', mini);
      remember(mini);
      syncMiniUi();
      if (!mini) { window.requestAnimationFrame(revealActive); }
    }

    // 1. Chevron state: Bootstrap 5 collapse events bubble, so one delegated handler covers every group.
    function groupState(e, open) {
      var list = e.target;
      if (!list || !list.classList || !list.classList.contains('sidebar-submenu')) { return; }
      var button = sidebar.querySelector('[aria-controls="' + list.id + '"]');
      if (!button) { return; }
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.classList.toggle('collapsed', !open);
      var heading = button.closest('.sidebar-heading');
      if (heading) { heading.classList.toggle('is-open', open); }
    }
    document.addEventListener('show.bs.collapse', function (e) { groupState(e, true); });
    document.addEventListener('hide.bs.collapse', function (e) { groupState(e, false); });

    // Mobile: bring the active link into view each time the off-canvas sidebar finishes opening.
    document.addEventListener('shown.bs.collapse', function (e) {
      if (e.target === sidebar) { revealActive(); }
    });

    // 2. Mini mode. A click on a group icon while mini expands the sidebar and opens that group.
    //    Capture phase on window: runs before Bootstrap's delegated (document-level, capture) toggle
    //    whatever the script order. If the group is already open we swallow the click, otherwise
    //    Bootstrap's toggle opens it right after.
    window.addEventListener('click', function (e) {
      if (!isMini() || !e.target.closest) { return; }
      var button = e.target.closest('.sidebar-group-toggle');
      if (!button || !sidebar.contains(button)) { return; }
      var list = document.getElementById(button.getAttribute('aria-controls'));
      setMini(false);
      if (list && list.classList.contains('show')) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    if (miniToggle) {
      miniToggle.addEventListener('click', function () { setMini(!isMini()); });
    }

    if (desktop.addEventListener) { desktop.addEventListener('change', syncMiniUi); }
    else if (desktop.addListener) { desktop.addListener(syncMiniUi); }

    syncMiniUi();
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', revealActive); }
    else { revealActive(); }
  })();
</script>
