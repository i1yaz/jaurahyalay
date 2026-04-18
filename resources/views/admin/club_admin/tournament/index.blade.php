@extends('admin.master')
@section('title')
    <title>Tournaments</title>
@endsection

@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
              <div class="col-sm-6">
                  <h1 class="m-0 text-dark">Tournaments</h1>
              </div><!-- /.col -->
              <div class="col-sm-6">
                <a class='float-right btn btn-success' href="{{ route('club_admin.tournament.create') }}">Add New</a>
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
      <div class="card-body  table-responsive p-0">
        @include('admin.include.messages')
        <div class="box-body">
            <table id="example1" class="table table-head-fixed text-nowrap table-striped table-bordered">
              <thead>
                <tr>
                  <th>S.No</th>
                  <th>Name</th>
                  <th>Start Date #</th>
                  <th>Show in Results</th>
                  <th>Show on Front Page</th>
                  <th>Hide From Public</th>
                  <th>Sort</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($tournaments as $tournament)
                  <tr>
                      <td>{{ (($page-1)*$records)+($loop->index + 1) }}</td>
                      <td>{{ $tournament->name}}</td>
                    <td>
                      @php
                        $date = \Carbon\Carbon::parse($tournament->start_date);
                        echo $date->settings(['toStringFormat' => ' j F, Y']);
                      @endphp
                    </td>
                      <td>
                          @if($tournament->status)
                              <strong></strong>
                              <span class="badge badge-success">Show</span>
                          @else
                              <span class="badge badge-danger">Hide</span>
                          @endif
                      </td>
                    <td>
                      @if($tournament->show)
                      <strong></strong>
                      <span class="badge badge-success">Show</span>
                      @else
                      <span class="badge badge-danger">Hide</span>
                      @endif
                    </td>
                    <td>
                      @if(!$tournament->public_hide)
                      <span class="badge badge-danger">Hide</span>
                      @else
                      <span class="badge badge-success">Show</span>
                      @endif
                    </td>
                    <td>{{ $tournament->sort}}</td>

                    <td>
                        <a href="{{ route('club_admin.tournament.edit',$tournament->id) }}"><span class="fas fa-edit" ></span></a>
                    </td>
                    <td>
                        <form id="delete-form-{{ $tournament->id }}" method="post" action="{{ route('club_admin.tournament.destroy',$tournament->id) }}" style="display: none">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        </form>
                        <a href="" onclick="
                        if(confirm('Are you sure, You Want to delete this?'))
                            {
                            event.preventDefault();
                            document.getElementById('delete-form-{{ $tournament->id }}').submit();
                            }
                            else{
                            event.preventDefault();
                            }" ><span class="fas fa-trash-alt" ></span></a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

        <!-- /.row -->
      </div>
        <div class="col-sm-12 col-md7">
            {{ $tournaments->links() }}
        </div>
      <!-- ./card-body -->
      <div class="card-footer">

      </div>
      <!-- /.card-footer -->
    </div>
    <!-- /.card -->
  </div>
@endsection
