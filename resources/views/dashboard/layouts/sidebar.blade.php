<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-light" style="height: calc(100vh - 10px); overflow-y: auto;">
    <div class="position-sticky pt-3">
      @can('admin')
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" aria-current="page" href="/dashboard">
            <span data-feather="home"></span>
            Dashboard
          </a>
        </li>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>Master Data</span>
        </h6>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/series*') ? 'active' : '' }}" href="/dashboard/series">
            <span data-feather="grid"></span>
            Series
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/suppliers*') ? 'active' : '' }}" href="/dashboard/suppliers">
            <span data-feather="package"></span>
            Suppliers
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/customers*') ? 'active' : '' }}" href="/dashboard/customers">
            <span data-feather="users"></span>
            Customers
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/items*') ? 'active' : '' }}" href="/dashboard/items">
            <span data-feather="box"></span>
            Items
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/hampers*') ? 'active' : '' }}" href="/dashboard/hampers">
            <span data-feather="gift"></span>
            Hampers
          </a>
        </li>
      </ul>
      @endcan

      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Transaction</span>
      </h6>
      <ul class="nav flex-column">
        @can('admin')
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/purchases*') ? 'active' : '' }}" href="/dashboard/purchases">
            <span data-feather="shopping-cart"></span>
            Purchase
          </a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/sales*') ? 'active' : '' }}" href="/dashboard/sales">
            <span data-feather="dollar-sign"></span>
            Sales
          </a>
        </li>
      </ul>

      @can('admin')
      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Stock Opname</span>
      </h6>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockopname*') ? 'active' : '' }}" href="/dashboard/stockopname">
            <span data-feather="database"></span>
            Stock Opname
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockin*') ? 'active' : '' }}" href="/dashboard/stockin">
            <span data-feather="plus" style="stroke: black;"></span>
            Stock In History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/stockout*') ? 'active' : '' }}" href="/dashboard/stockout">
            <span data-feather="minus" style="stroke: black;"></span>
            Stock Out History
          </a>
        </li>
      </ul>

      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
        <span>Report</span>
      </h6>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/sales/history*') ? 'active' : '' }}" href="/dashboard/sales/history">
            <span data-feather="clipboard"></span>
            Sales History
          </a>
        </li>
      </ul>
      @endcan

      <hr>
      <form action="/logout" method="POST">
        @csrf
        <button type="submit" class="btn nav-link mb-3">Logout <span data-feather="log-out"></span></button>
      </form>
    </div>
</nav>