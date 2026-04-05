@extends('admin.master')
@section('title')
  <title>Settings</title>
@endsection
@push('css')
    <style>
        img {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            width: 150px;
        }
        img:hover {
            box-shadow: 0 0 2px 1px rgba(0, 140, 186, 0.5);
        }
    </style>
@endpush
@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Sliders</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <a class='float-right btn btn-success' href="{{ route('website.create') }}">Add Sliders</a>
            </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
@endsection

@section('content')
    @include('admin.website.settings.sliders')
    @include('admin.website.settings.auto_update_time')
    @include('admin.website.settings.guest_link')
    @include('admin.website.settings.first_winner_last_winner')
@endsection
