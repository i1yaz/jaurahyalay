@extends('admin.master')
@section('title')
<title>Create tournaments</title>
@endsection
@push('css')
    <!-- select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
@endpush
@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"> Create tournament</h1>
            </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
@endsection

@section('content')
<div class="col-md-12">
    <div class="card card-secondary">
      <div class="card-header">
        <h5 class="card-title"></h5>

        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      <!-- /.card-header -->
      <div class="card-body">

        <form role="form" action="{{ route('tournament.store') }}" method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
          <div class="box-body">
            <div class="offset-lg-3 col-lg-6">
                  @include('admin.include.messages')

                <div class="form-group">
                  <label>Poster</label>
                  <input class="form-control" type="file" name="poster">
                </div>

                <div class="form-group">
                  <label>Club Name</label>
                  <select class="form-control select2 select2-hidden-accessible" name="club" data-placeholder="Select club" style="width: 100%;">
                    @foreach ($clubs as $club)
                      <option value="{{$club->id}}" >{{$club->name}}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group">
                  <label for="name">Tournament Name</label>
                  <input type="text" class="form-control" id="name" name="name" placeholder="tournament Name" value="{{ old('name') }}"required>
                </div>

                <div class="form-group">
                    <label for="pigeons">No of Pigeons</label>
                    <input type="number" class="form-control" id="pigeons" name="pigeons" placeholder="pigeons" value="{{ old('pigeons')??0 }}"required>
                </div>

                <div class="form-group">
                    <label for="supporter">Helper pigeons</label>
                    <input type="number" class="form-control" id="supporter" name="supporter" placeholder="supporter" value="{{ old('supporter')??0 }}"required>
                </div>
                <div class="form-group">
                  <label>Type</label>
                  <select class="form-control select2 select2-hidden-accessible" name="type"   style="width: 100%;">
                    <option value="OPEN" selected="selected" >Open</option>
                    <option value="FIXED">Fixed</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Players</label>
                  <select class="form-control select2 select2-hidden-accessible" name="players[]" multiple="multiple"  style="width: 100%;">
                    @foreach ($players as $player)
                      <option value="{{$player->id}}" >{{$player->name}}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-check">
                    <input type="checkbox"name="status" class="form-check-input" id="status" checked>
                    <label class="form-check-label"  for="status">Show in Results </label>
                </div>
                <div class="form-check">
                    <input type="checkbox"name="show" class="form-check-input" id="show" checked>
                    <label class="form-check-label"  for="show">Show on Front Page</label>
                </div>

                <div class="form-check">
                  <input type="checkbox"name="public_hide" class="form-check-input" id="public_hide">
                  <label class="form-check-label"  for="public_hide">Hide From Public</label>
                </div>

                <div class="form-check">
                  <input type="checkbox"name="allow_double_stamp" class="form-check-input" id="allow_double_stamp">
                  <label class="form-check-label"  for="allow_double_stamp">Allow Double Stamp?</label>
                </div>

                <div class="form-group days">
                    <label for="days">Days</label>
                    <input type="text" class="form-control " id="days" name="days" placeholder="days" value="{{ old('days')??1 }}" required>
                </div>

                <div class="form-group date">
                  <label for="date">Start Date</label>
                  <input type="date" class="form-control" id="date" name="date[]" value="{{ date('Y-m-d')  }}" required>
                </div>

                <div class="form-group">
                  <label for="time">Start Time</label>
                  <input type="time" class="form-control" id="time" name="time" value="06:00" required>
                </div>

                <div class="form-group total-prize">
                  <label for="prize">Total Prizes</label>
                  <input type="text" class="form-control" id="prize" value="0">
                </div>
                <div class="form-group">
                    <label>Tournament admin</label>
                    <select class="form-control select2 select2-hidden-accessible" name="tournament_admins[]" multiple="multiple"  style="width: 100%;">
                        @foreach ($admins as $admin)
                            <option value="{{$admin->id}}" >{{$admin->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                  <a href='{{ route('tournament.index') }}' class="btn btn-warning">Back</a>
                  <button type="submit" class="btn btn-primary float-right">Save</button>
                </div>
            </div>
          </div>
        </form>

        <!-- /.row -->
      </div>
      <!-- ./card-body -->
      <div class="card-footer">
      </div>
      <!-- /.card-footer -->
    </div>
    <!-- /.card -->
  </div>
@endsection
@push('js')

<script>
    $('#date').click(function (e) {
      if(!$('#days').val() || $('#days').val()<1) {
        e.preventDefault();
        $("#days").focus();
        $('.days').addClass('has-error');
      }else{
        $('.days').removeClass('has-error')
      }
    });
    $('#days').on('change keyup',function (e) {
      $('.flying-days').remove();
      let days = $('#days').val();
      for (let index = 1; index < days; index++) {
        $('.date').after(
            '<div class="form-group days-'+(days-index+1)+' flying-days">\n' +
                '<label for="date-'+(days-index+1)+'">Day '+(days-index+1)+'</label>\n' +
                '<input type="date" class="form-control" id="date-'+(days-index+1)+'" name="date[]" value="{{ date("Y-m-d")  }}" required>\n' +
            '</div>'
        );
      }
    });
  </script>
  <script>
$('#prize').on('change keyup',function (e) {
      $('.prize').remove();
      let total = $('#prize').val();
      for (let index = 0; index < total; index++) {
        $('.total-prize').after(
            '<div class="form-group prize-'+(total-index)+' prize">\n' +
                '<label for="prize-'+(total-index+1)+'"> Prize '+(total-index)+'</label>\n' +
                '<input type="text" class="form-control" id="prize-'+(total-index)+'" name="prize['+(total-index)+']" value="">\n' +
            '</div>'
        );
      }
    });
  </script>
  <!-- select2 -->
  <script src="{{ asset('adminlte/plugins/select2/js/select2.min.js') }}"></script>
  <script>
    $(document).ready(function () {
      $('.select2').select2({
      });
    });
  </script>
@endpush
