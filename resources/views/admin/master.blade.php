
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    @yield('title')

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
     <title></title>

    @stack('css')
  </head>
  <body class="hold-transition sidebar-mini @if(isset($isGuest) && $isGuest) sidebar-collapse @endif">
  <div class="wrapper">

  @if(!(isset($isGuest) && $isGuest))
  @include('admin.include.topbar')
  @endif
    <!-- Main Sidebar Container -->
    @if(!(isset($isGuest) && $isGuest))
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      @include('admin.include.sidebar-profile')
    </aside>
    @endif

    <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
      @yield('breadcrumb')
      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">
          <div class="row">
              @yield('content')
          </div>
          <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
      </div>
      <!-- /.content -->
  </div>
    <!-- /.content-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    @if(!(isset($isGuest) && $isGuest))
    @include('admin.include.footer')
    @endif
  </div>
  <!-- ./wrapper -->

  <!-- REQUIRED SCRIPTS -->

  <!-- jQuery -->
  <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js')}} "></script>
  <!-- Bootstrap -->
  <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <!-- AdminLTE -->
  <script src="{{ asset('adminlte/dist/js/adminlte.js') }}"></script>

  @stack('js')
  </body>
  </html>
