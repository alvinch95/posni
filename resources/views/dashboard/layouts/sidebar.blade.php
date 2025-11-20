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
      </ul>
      @endcan

      <div class="sidebar-heading mt-4">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#attendanceSubmenu" aria-expanded="false" aria-controls="attendanceSubmenu">
          <span>Absensi</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="attendanceSubmenu" class="nav flex-column collapse">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('attendance/checkin*') ? 'active' : '' }}" href="/attendance/checkin">
            <span data-feather="user-check"></span>
            Checkin/Checkout
          </a>
        </li>
      </ul>

      @can('admin')
      <div class="sidebar-heading mt-3">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#masterDataSubmenu" aria-expanded="false" aria-controls="masterDataSubmenu">
          <span>Master Data</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="masterDataSubmenu" class="nav flex-column collapse">
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

      @can('admin')
      <div class="sidebar-heading mt-3">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#transactionSubmenu" aria-expanded="false" aria-controls="transactionSubmenu">
          <span>Transaction</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="transactionSubmenu" class="nav flex-column collapse {{ Request::is('dashboard/purchases*') ? 'show' : '' }}{{ Request::is('dashboard/sales') ? 'show' : '' }}">
        @can('admin')
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/purchases*') ? 'active' : '' }}" href="/dashboard/purchases">
            <span data-feather="shopping-cart"></span>
            Purchase
          </a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/sales') ? 'active' : '' }}" href="/dashboard/sales">
            <span data-feather="dollar-sign"></span>
            Sales
          </a>
        </li>
      </ul>
      @endcan

      @can('admin')
      <div class="sidebar-heading mt-3">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#stockOpnameSubmenu" aria-expanded="false" aria-controls="stockOpnameSubmenu">
          <span>Stock Opname</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="stockOpnameSubmenu" class="nav flex-column collapse {{ Request::is('dashboard/stock*') ? 'show' : '' }}">
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
      
      <div class="sidebar-heading mt-3">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#cashBalanceSubmenu" aria-expanded="false" aria-controls="cashBalanceSubmenu">
          <span>Cash Balance</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="cashBalanceSubmenu" class="nav flex-column collapse {{ Request::is('dashboard/cashbalances*') ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/cashbalances/create*') ? 'active' : '' }}" href="/dashboard/cashbalances/create">
            <span data-feather="check-circle" style="stroke: black;"></span>
            New Transaction
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/cashbalances') ? 'active' : '' }}" href="/dashboard/cashbalances">
            <span data-feather="book"></span>
            Cash History
          </a>
        </li>
      </ul>

      <div class="sidebar-heading mt-3">
        <button class="btn btn-link text-left px-3 text-muted" data-bs-toggle="collapse" data-bs-target="#reportSubmenu" aria-expanded="false" aria-controls="reportSubmenu">
          <span>Report</span>
          <span class="arrow-down" data-feather="arrow-down" style="display: inline;"></span>
          <span class="arrow-up" data-feather="arrow-up" style="display: none;"></span>
        </button>
      </div>
      <ul id="reportSubmenu" class="nav flex-column collapse {{ Request::is('dashboard/sales/history*') ? 'show' : '' }}{{ Request::is('dashboard/shopeereminder*') ? 'show' : '' }}">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/sales/history*') ? 'active' : '' }}" href="/dashboard/sales/history">
            <span data-feather="clipboard"></span>
            Sales History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('dashboard/shopeereminder*') ? 'active' : '' }}" href="/dashboard/shopeereminder">
            <span data-feather="check-square"></span>
            Shopee Reminder
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

<script>
  // Change arrow direction on collapse/expand
  var reportSubmenu = document.getElementById('reportSubmenu');
  if(reportSubmenu){
    reportSubmenu.addEventListener('show.bs.collapse', function() {
      document.querySelector('[data-bs-target="#reportSubmenu"] .arrow-down').style.display = 'none';
      document.querySelector('[data-bs-target="#reportSubmenu"] .arrow-up').style.display = 'inline';
    });

    reportSubmenu.addEventListener('hide.bs.collapse', function() {
      document.querySelector('[data-bs-target="#reportSubmenu"] .arrow-down').style.display = 'inline';
      document.querySelector('[data-bs-target="#reportSubmenu"] .arrow-up').style.display = 'none';
    });
  }

  var cashBalanceSubmenu = document.getElementById('cashBalanceSubmenu');
  if(cashBalanceSubmenu){
    cashBalanceSubmenu.addEventListener('show.bs.collapse', function() {
      document.querySelector('[data-bs-target="#cashBalanceSubmenu"] .arrow-down').style.display = 'none';
      document.querySelector('[data-bs-target="#cashBalanceSubmenu"] .arrow-up').style.display = 'inline';
    });
  
    cashBalanceSubmenu.addEventListener('hide.bs.collapse', function() {
      document.querySelector('[data-bs-target="#cashBalanceSubmenu"] .arrow-down').style.display = 'inline';
      document.querySelector('[data-bs-target="#cashBalanceSubmenu"] .arrow-up').style.display = 'none';
    });
  }

  var stockOpnameSubmenu = document.getElementById('stockOpnameSubmenu');
  if(stockOpnameSubmenu){
    stockOpnameSubmenu.addEventListener('show.bs.collapse', function() {
      document.querySelector('[data-bs-target="#stockOpnameSubmenu"] .arrow-down').style.display = 'none';
      document.querySelector('[data-bs-target="#stockOpnameSubmenu"] .arrow-up').style.display = 'inline';
    });
  
    stockOpnameSubmenu.addEventListener('hide.bs.collapse', function() {
      document.querySelector('[data-bs-target="#stockOpnameSubmenu"] .arrow-down').style.display = 'inline';
      document.querySelector('[data-bs-target="#stockOpnameSubmenu"] .arrow-up').style.display = 'none';
    });
  }

  var transactionSubmenu = document.getElementById('transactionSubmenu');
  if(transactionSubmenu){
    transactionSubmenu.addEventListener('show.bs.collapse', function() {
      document.querySelector('[data-bs-target="#transactionSubmenu"] .arrow-down').style.display = 'none';
      document.querySelector('[data-bs-target="#transactionSubmenu"] .arrow-up').style.display = 'inline';
    });
  
    transactionSubmenu.addEventListener('hide.bs.collapse', function() {
      document.querySelector('[data-bs-target="#transactionSubmenu"] .arrow-down').style.display = 'inline';
      document.querySelector('[data-bs-target="#transactionSubmenu"] .arrow-up').style.display = 'none';
    });
  }

  var masterDataSubmenu = document.getElementById('masterDataSubmenu');
  if(masterDataSubmenu){
    masterDataSubmenu.addEventListener('show.bs.collapse', function() {
      document.querySelector('[data-bs-target="#masterDataSubmenu"] .arrow-down').style.display = 'none';
      document.querySelector('[data-bs-target="#masterDataSubmenu"] .arrow-up').style.display = 'inline';
    });
  
    masterDataSubmenu.addEventListener('hide.bs.collapse', function() {
      document.querySelector('[data-bs-target="#masterDataSubmenu"] .arrow-down').style.display = 'inline';
      document.querySelector('[data-bs-target="#masterDataSubmenu"] .arrow-up').style.display = 'none';
    });
  }
</script>