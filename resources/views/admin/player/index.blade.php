@extends('admin.master')
@section('title')
  <title>Players</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
  <style>
    .dataTables_wrapper  {
        margin-bottom: 1rem;
        margin-top: 1rem;
        margin-left: 0.3rem;
        margin-right: 0.3rem;
    }

</style>
@endsection

@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Players</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <a class='float-right btn btn-success' href="{{ route('player.create') }}">Add New</a>
              <button id="bulk-delete" class="btn btn-danger float-right mr-2" style="display:none;">Delete Selected</button>
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
          <div class="row mr-2" style="width: auto;">
            <div class="col-md-6 p-0 mr-2 mb-2 mb-md-0">
                <select id="filter-club" class="form-control form-control-sm">
                    <option value="all">All Clubs</option>
                    @foreach($clubs as $club)
                        <option value="{{ $club->id }}">{{ $club->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 p-0">
                <select id="filter-status" class="form-control form-control-sm">
                    <option value="all">All Players</option>
                    <option value="played">Has Tournament</option>
                    <option value="not_played">No Tournament</option>
                </select>
            </div>
          </div>
        </div>
      </div>
      <!-- /.card-header -->
      <div class="card-body table-responsive p-0">
        @include('admin.include.messages')
        <div class="box-body">
          <table id="players" class="table table-head-fixed text-nowrap table-striped table-bordered">
            <thead>
              <tr>
                <th width="10"><input type="checkbox" id="select-all"></th>
                <th>Player Name</th>
                <th>Club</th>
                <th>Phone #</th>
                <th>City</th>
                <th>Province</th>
                <th>Tournaments</th>
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <!-- /.row -->
      </div>
      <div class="col-sm-12 col-md7">
        {{-- {{ $players->links() }} --}}
      </div>
      <!-- ./card-body -->
      <div class="card-footer">

      </div>
      <!-- /.card-footer -->
    </div>
    <!-- /.card -->
  </div>

<!-- Tournament Modal -->
<div class="modal fade" id="tournamentModal" tabindex="-1" role="dialog" aria-labelledby="tournamentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tournamentModalLabel">Player Tournaments</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <ul id="tournament-list" class="list-group">
          <!-- Tournaments will be loaded here -->
        </ul>
        <div id="pagination-container" class="mt-3">
          <!-- Pagination will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('js')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
      $(document).ready(function () {
        var table = $('#players').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
            url: '{{ route("players.data") }}',
            data: function (d) {
                d.club_id = $('#filter-club').val();
                d.status = $('#filter-status').val();
            }
          },
          pageLength: 25,
          order: [[1, 'asc']], // Order by Name by default (index 1 since checkbox is 0)
          columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'club', name: 'club' },
            { data: 'phone', name: 'phone' },
            { data: 'city', name: 'city' },
            { data: 'province', name: 'province' },
            { data: 'tournaments', name: 'tournaments', orderable: false, searchable: false, className: 'text-center' },
            { data: 'edit', name: 'edit', orderable: false, searchable: false },
            { data: 'delete', name: 'delete', orderable: false, searchable: false }
          ]
        });

        $('#filter-club, #filter-status').on('change', function() {
            table.draw();
        });

        // Handle Select All
        $('#select-all').on('click', function() {
            $('.player-checkbox').prop('checked', this.checked);
            toggleBulkDeleteButton();
        });

        // Handle Individual Checkbox
        $('#players').on('change', '.player-checkbox', function() {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
            if ($('.player-checkbox:checked').length === $('.player-checkbox').length) {
                $('#select-all').prop('checked', true);
            }
            toggleBulkDeleteButton();
        });

        function toggleBulkDeleteButton() {
            if ($('.player-checkbox:checked').length > 0) {
                $('#bulk-delete').show();
            } else {
                $('#bulk-delete').hide();
            }
        }

        // Handle Bulk Delete
        $('#bulk-delete').on('click', function() {
            var ids = [];
            $('.player-checkbox:checked').each(function() {
                ids.push($(this).val());
            });

            if (ids.length > 0 && confirm('Are you sure you want to delete ' + ids.length + ' players?')) {
                $.ajax({
                    url: '{{ route("player.bulk_delete") }}',
                    type: 'POST',
                    data: {
                        ids: ids,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('#select-all').prop('checked', false);
                            $('#bulk-delete').hide();
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        });

        // Handle Tournament Modal
        $('#players').on('click', '.view-tournaments', function() {
            var playerId = $(this).data('id');
            loadTournaments(playerId, 1);
        });

        function loadTournaments(playerId, page) {
            var url = '{{ route("player.tournaments", ":id") }}'.replace(':id', playerId) + '?page=' + page;
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    var list = $('#tournament-list');
                    list.empty();
                    response.data.forEach(function(tournament) {
                        list.append('<li class="list-group-item">' + tournament.name + '</li>');
                    });

                    if (response.data.length === 0) {
                        list.append('<li class="list-group-item">No tournaments found.</li>');
                    }

                    // Simple pagination
                    var pagination = $('#pagination-container');
                    pagination.empty();
                    if (response.last_page > 1) {
                        var nav = $('<nav aria-label="Page navigation"></nav>');
                        var ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
                        
                        for (var i = 1; i <= response.last_page; i++) {
                            var li = $('<li class="page-item ' + (i === response.current_page ? 'active' : '') + '"></li>');
                            var a = $('<a class="page-link" href="javascript:void(0)" data-page="' + i + '">' + i + '</a>');
                            li.append(a);
                            ul.append(li);
                        }
                        nav.append(ul);
                        pagination.append(nav);

                        pagination.find('.page-link').on('click', function() {
                            loadTournaments(playerId, $(this).data('page'));
                        });
                    }

                    $('#tournamentModal').modal('show');
                }
            });
        }
      });
      
      const observer = lozad();
      observer.observe();
    </script>
@endpush
