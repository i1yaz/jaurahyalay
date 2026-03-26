<style>
    @media (min-width: 992px) {
        .navbar-expand-lg .navbar-nav {
            -ms-flex-direction: column;
            flex-direction: row !important;
        }
    }

    @media screen and (min-width: 480px) {
        body {
            -ms-flex-direction: column;
            flex-direction: column !important;
        }
    }

    .navbar-dark .navbar-nav .nav-link {
        color: white;
    }
</style>

<style>
    /* Optional: keeps background active on hover */
    .navbar-nav .dropdown:hover>.nav-link {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .navbar-nav .dropdown:hover .dropdown-menu {
        display: block;
        margin-top: 0;
    }

    /* Match .bg-navbar background color */
    .dropdown-menu {
        background-color: #1a1a2e !important;
        border: none;
        box-shadow: none;
    }

    .dropdown-item {
        color: white !important;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: white !important;
    }

    /* Make dropdown absolute on all screen sizes */
    .dropdown-menu {
        position: absolute !important;
        background-color: #1a1a2e !important;
        border: none;
        box-shadow: none;
        z-index: 1000;
        top: 100%;
        left: 0;
    }

    /* Hover only for large screens */
    @media (min-width: 992px) {
        .navbar-nav .dropdown:hover>.nav-link {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-nav .dropdown:hover .dropdown-menu {
            display: block;
            margin-top: 0;
        }
    }
</style>


<nav class="navbar navbar-expand-lg navbar-dark bg-navbar mt-3" style="font-size: 1.2rem; padding: 0.5rem 1rem;">
    <ul class="navbar-nav mr-auto" style="flex-direction: row;">
        <li class="nav-item">
            <a class="nav-link" href="/">Home</a>
        </li>

        <!-- Clubs Dropdown -->
        <li class="nav-item dropdown" style="margin-left: 15%;">
            <a class="nav-link dropdown-toggle" href="#" id="clubsDropdown" role="button">
                Clubs
            </a>
            <div class="dropdown-menu" aria-labelledby="clubsDropdown">
                @foreach ($activeClubs as $club)
                    <a class="dropdown-item @if($club->id == $segmentClub) active @endif"
                        href="{{ route('result.club', ['club' => $club->id]) }}" style="margin-top: 20px;">
                        {{ $club->name }}
                    </a>
                @endforeach
            </div>
        </li>

        <li class="nav-item @if($route == 'weather') active @endif" style="margin-left: 15%;">
            <a class="nav-link" href="{{ route('result.club', ['club' => 1]) }}">Tournaments</a>
        </li>

        <li class="nav-item @if($route == 'weather') active @endif" style="margin-left: 15%;">
            <a class="nav-link" href="{{ route('weather') }}">Weather</a>
        </li>
        <li class="nav-item" style="margin-left: 15%;">
            <a class="nav-link @if($route == 'contact') active @endif" href="{{ route('contact') }}">Contact</a>
        </li>
    </ul>

    <span class="navbar-text" style="color: aliceblue"><b id="online-users"></b></span>
    <span class="float-right mr-3 ml-4" style="color: aliceblue" id="localdate"></span>
</nav>