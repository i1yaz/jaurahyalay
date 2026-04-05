<style>
    /* Navbar background */
    .bg-navbar {
        background: linear-gradient(to right, rgba(63,0,255), rgb(75, 18, 243)) !important;
    }

    /* Navbar link color & font size */
    .navbar.bg-navbar .nav-link {
        color: white !important;
        font-size: 1.15rem !important;
        font-weight: 500;
    }

    .navbar.bg-navbar .nav-link:hover,
    .navbar.bg-navbar .nav-link:focus {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .navbar.bg-navbar .nav-item.active .nav-link,
    .navbar.bg-navbar .nav-link.active {
        color: white !important;
        font-weight: 600;
    }

    /* Main navbar container */
    .custom-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        padding: 0.5rem 1rem;
        margin-top: 1rem;
    }

    /* Navigation items container */
    .nav-items {
        display: flex;
        align-items: center;
        flex-direction: row !important;
        flex-wrap: nowrap;
        gap: 0;
        /* Removed overflow-x: auto to prevent clipping dropdowns */
        scrollbar-width: none;
    }

    /* Override Bootstrap navbar-nav vertical layout */
    .nav-items.navbar-nav {
        flex-direction: row !important;
    }

    .nav-items.navbar-nav .nav-item {
        display: flex;
        flex-direction: row !important;
    }

    /* Visual separator between nav items */
    .nav-items .nav-item {
        position: relative;
        flex-shrink: 0;
    }

    .nav-items .nav-item:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        height: 60%;
        width: 1px;
        background-color: rgba(255, 255, 255, 0.3);
    }

    /* Add padding instead of gap for separator to work correctly */
    .nav-items .nav-item .nav-link {
        padding: 0.5rem 1rem;
    }

    .nav-items::-webkit-scrollbar {
        display: none;
    }

    /* Info section (users & time) */
    .navbar-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        color: white;
        flex-shrink: 0;
        padding-left: 1rem;
        font-size: 1rem;
        line-height: 1.3;
        border-left: 1px solid rgba(255, 255, 255, 0.3);
        padding-left: 1.5rem;
    }

    .navbar-info .info-line {
        white-space: nowrap;
    }

    /* Dropdown styling */
    .nav-items .dropdown {
        position: relative;
    }

    .nav-items .dropdown > .nav-link {
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .nav-items .dropdown:hover > .nav-link,
    .nav-items .dropdown .nav-link:focus {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* PREMIUM DROPDOWN MENU */
    .nav-items .dropdown .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        display: block !important;
        visibility: hidden;
        opacity: 0;
        transform: translateY(15px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        background-color: #1a1a2e !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border-radius: 8px;
        margin-top: 5px;
        min-width: 250px;
        z-index: 1050;
    }

    /* Show dropdown on hover or when Bootstrap ".show" class is present */
    .nav-items .dropdown:hover .dropdown-menu,
    .nav-items .dropdown.show .dropdown-menu,
    .nav-items .dropdown .dropdown-menu.show {
        visibility: visible !important;
        opacity: 1 !important;
        transform: translateY(0) !important;
    }

    .dropdown-item {
        color: white !important;
        padding: 0.75rem 1.25rem;
        font-weight: 400;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        padding-left: 1.5rem; /* Subtle slide effect on hover */
    }

    .dropdown-item.active {
        background-color: rgba(63, 0, 255, 0.5) !important;
        color: white !important;
        font-weight: 600;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .custom-navbar {
            padding: 0.5rem 0.5rem;
        }

        .nav-items {
            gap: 0;
        }

        .navbar-info {
            font-size: 0.75rem;
            padding-left: 0.5rem;
        }
    }
</style>

<nav class="navbar custom-navbar bg-navbar">
    <!-- Navigation Items -->
    <div class="nav-items navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="/">Home</a>
        </li>

        <!-- Clubs Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="javascript:void(0)" id="clubsDropdown" role="button" 
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Clubs
            </a>
            <div class="dropdown-menu" aria-labelledby="clubsDropdown">
                @foreach ($activeClubs as $club)
                    <a class="dropdown-item @if($club->id == $segmentClub) active @endif"
                        href="{{ route('result.club', ['club' => $club->id]) }}">
                        {{ $club->name }}
                    </a>
                @endforeach
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('result.club', ['club' => 1]) }}">Tournaments</a>
        </li>

        <!-- Events (Active Tournaments) Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="javascript:void(0)" id="eventsDropdown" role="button" 
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Events
            </a>
            <div class="dropdown-menu" aria-labelledby="eventsDropdown">
                @foreach ($activeTournaments as $tournament)
                    <a class="dropdown-item @if(isset($segmentTournament) && $tournament->id == $segmentTournament) active @endif"
                        href="{{ route('result.tournament', ['club_id' => $tournament->club_id, 'tournament_id' => $tournament->id]) }}">
                        {{ $tournament->name }}
                    </a>
                @endforeach
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('weather') }}">Weather</a>
        </li>

        <li class="nav-item">
            <a class="nav-link @if($route == 'contact') active @endif" href="{{ route('contact') }}">Contact</a>
        </li>
    </div>

    <!-- Online Users & Time -->
    <div class="navbar-info">
        <span id="online-users" class="info-line"><b>Users: 0</b></span>
        <span id="localdate" class="info-line">00:00:00</span>
    </div>
</nav>