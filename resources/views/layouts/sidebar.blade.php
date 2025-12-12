<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('home') }}" class="brand-link">
        <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTE Logo"
            class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name', 'Tracebility') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class -->
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->is('home') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>


                <li class="nav-header">MASTER DATA</li>

                <li class="nav-item has-treeview {{ request()->is('master-data*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('master-data*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-database"></i>
                        <p>
                            Master Data
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('itemmaster.index') }}"
                                class="nav-link {{ request()->routeIs('itemmaster.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Item Master</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('itemcategory.index') }}"
                                class="nav-link {{ request()->routeIs('itemcategory.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Item Category</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('master-data.unit.index') }}"
                                class="nav-link {{ request()->routeIs('master-data.unit.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>UOM (Unit)</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item has-treeview {{ request()->is('production*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->is('production*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>
                            Production
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-header">PRODUCTION PROCESS</li>
                        <li class="nav-item">
                            <a href="{{ route('production.work-order.index') }}"
                                class="nav-link {{ request()->routeIs('production.work-order.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Work Order</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('production.wo-transaction.index') }}"
                                class="nav-link {{ request()->routeIs('production.wo-transaction.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>WO Transaction</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('production.wo-lot.index') }}"
                                class="nav-link {{ request()->routeIs('production.work-order-lot.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>WO Lot</p>
                            </a>
                        </li>
                        <li class="nav-header">PD MASTER DATA</li>
                        <li class="nav-item">
                            <a href="{{ route('production.bom.index') }}"
                                class="nav-link {{ request()->routeIs('production.bom.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>BOM</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('production.master-process.index') }}"
                                class="nav-link {{ request()->routeIs('production.master-process.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Master Process</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('production.setting-process.index') }}"
                                class="nav-link {{ request()->routeIs('production.setting-process.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Setting Process</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">MANAGEMENT</li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            Users
                            <span class="badge badge-info right">6</span>
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>

                <li class="nav-header">HELP</li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>Help & Support</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
