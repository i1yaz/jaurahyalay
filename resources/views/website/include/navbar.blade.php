<style>
    /* Premium Floating Navbar Container */
    .custom-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 1.5rem;
        margin: 1.25rem 0 1rem;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(91, 126, 60, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.15);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Override Bootstrap navbar-nav default vertical styles */
    .nav-items.navbar-nav {
        display: flex;
        flex-direction: row !important;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    .nav-items.navbar-nav .nav-item {
        display: flex;
        flex-direction: row !important;
        position: relative;
        flex-shrink: 0;
    }

    /* Base Nav Item Link styling */
    .navbar.bg-navbar .nav-link {
        color: rgba(255, 255, 255, 0.95) !important;
        font-size: 1.05rem !important;
        font-weight: 600;
        padding: 0.5rem 1.1rem !important;
        border-radius: 30px;
        transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        position: relative;
    }

    /* Hover State: beautiful soft white translucent pill backdrop */
    .navbar.bg-navbar .nav-link:hover,
    .navbar.bg-navbar .nav-link:focus {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.15) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    /* Active State: solid white glass pill backdrop */
    .navbar.bg-navbar .nav-item.active .nav-link,
    .navbar.bg-navbar .nav-link.active {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.22) !important;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Caret transition for dropdowns */
    .dropdown-toggle::after {
        transition: transform 0.25s ease;
        margin-left: 0.4em;
    }
    .dropdown:hover .dropdown-toggle::after {
        transform: rotate(180deg);
    }

    /* Dropdown container */
    .nav-items .dropdown {
        position: relative;
    }

    /* PREMIUM DROPDOWN MENU */
    .nav-items .dropdown .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        display: block !important;
        visibility: hidden;
        opacity: 0;
        transform: translateY(12px) scale(0.97);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12), 0 5px 15px rgba(91, 126, 60, 0.05);
        border-radius: 14px;
        margin-top: 8px;
        min-width: 260px;
        padding: 0.5rem;
        z-index: 1050;
    }

    /* Show dropdown smoothly on hover */
    .nav-items .dropdown:hover .dropdown-menu,
    .nav-items .dropdown.show .dropdown-menu,
    .nav-items .dropdown .dropdown-menu.show {
        visibility: visible !important;
        opacity: 1 !important;
        transform: translateY(0) scale(1) !important;
    }

    .dropdown-item {
        color: var(--text-dark) !important;
        padding: 0.7rem 1.1rem;
        font-weight: 500;
        font-size: 0.95rem;
        border-radius: 8px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        margin-bottom: 2px;
        text-shadow: none;
    }

    .dropdown-item:last-child {
        margin-bottom: 0;
    }

    /* Premium item hover with theme tinted background */
    .dropdown-item:hover,
    .dropdown-item:focus {
        background-color: rgba(91, 126, 60, 0.08) !important;
        color: var(--primary-blue) !important;
        padding-left: 1.4rem; /* Subtle glide-right transition */
        box-shadow: none;
    }

    /* Active dropdown item */
    .dropdown-item.active {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-alt)) !important;
        color: white !important;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(91, 126, 60, 0.2);
    }

    /* Info Badge Widgets (Users & Time) */
    .navbar-info {
        display: flex;
        align-items: center;
        gap: 12px;
        color: white;
        flex-shrink: 0;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
        padding-left: 1.25rem;
        margin-left: 1rem;
    }

    /* Capsule Widget Style */
    .navbar-info .info-widget {
        display: flex;
        align-items: center;
        background: rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.4rem 1.1rem;
        border-radius: 30px;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    .navbar-info .info-widget:hover {
        background: rgba(0, 0, 0, 0.2);
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* Glowing Pulsing Status Dot */
    .status-dot {
        width: 8px;
        height: 8px;
        background-color: #39FF14; /* Vibrant Neo-Green */
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        box-shadow: 0 0 10px #39FF14, 0 0 20px rgba(57, 255, 20, 0.5);
        animation: pulse-dot 1.6s infinite ease-in-out;
    }

    @keyframes pulse-dot {
        0% {
            transform: scale(0.9);
            opacity: 0.8;
            box-shadow: 0 0 6px #39FF14, 0 0 12px rgba(57, 255, 20, 0.3);
        }
        50% {
            transform: scale(1.15);
            opacity: 1;
            box-shadow: 0 0 12px #39FF14, 0 0 24px rgba(57, 255, 20, 0.6);
        }
        100% {
            transform: scale(0.9);
            opacity: 0.8;
            box-shadow: 0 0 6px #39FF14, 0 0 12px rgba(57, 255, 20, 0.3);
        }
    }

    /* Desktop & Tablet Responsive Overrides */
    @media (max-width: 991px) {
        .custom-navbar {
            flex-direction: column;
            align-items: stretch;
            padding: 1rem;
            border-radius: 14px;
            gap: 12px;
        }

        .nav-items.navbar-nav {
            justify-content: center;
            gap: 4px;
        }

        .navbar.bg-navbar .nav-link {
            font-size: 0.95rem !important;
            padding: 0.4rem 0.8rem !important;
        }

        .navbar-info {
            border-left: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-left: 0;
            padding-top: 10px;
            margin-left: 0;
            justify-content: center;
        }

        .navbar-info .info-widget {
            font-size: 0.85rem;
            padding: 0.35rem 0.8rem;
        }
    }

    /* Small Mobile Screen Overrides */
    @media (max-width: 576px) {
        .custom-navbar {
            padding: 0.75rem 0.5rem;
            margin-top: 0.75rem;
        }

        .nav-items.navbar-nav {
            gap: 2px;
        }

        .navbar.bg-navbar .nav-link {
            font-size: 0.85rem !important;
            padding: 0.35rem 0.6rem !important;
        }

        .navbar-info {
            gap: 6px;
        }

        .navbar-info .info-widget {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
        }
    }
</style>

<nav class="navbar custom-navbar bg-navbar">
    <!-- Navigation Items -->
    <ul class="nav-items navbar-nav">
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
                Active Tournaments
            </a>
            <div class="dropdown-menu" aria-labelledby="eventsDropdown">
                @foreach ($activeNavbarTournaments as $tournament)
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
    </ul>

    <!-- Online Users & Live Time Capsule Badges -->
    <div class="navbar-info">
        <div class="info-widget online-users-widget">
            <span class="status-dot"></span>
            <span id="online-users"><b>Users: 0</b></span>
        </div>
        <div class="info-widget localdate-widget">
            <i class="far fa-clock mr-2"></i>
            <span id="localdate">00:00:00</span>
        </div>
    </div>
</nav>