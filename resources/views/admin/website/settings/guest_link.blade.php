<div class="col-md-12">
    <div class="card card-secondary">
      <div class="card-header">
        <h5 class="card-title">Guest Link Expiry Settings</h5>

        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
        </div>
      </div>
      @php
          $key = $settings->where('key', 'signed_url_expiry_hours')->first();
          $currentValue = $key ? $key->value : 24;
      @endphp
      <!-- /.card-header -->
      <div class="card-body">
        <div class="box-body table-responsive p-0">
            <form role="form" action="{{ route('website.guest_link_settings') }}" method="post">
                {{ csrf_field() }}
              <div class="box-body">
                <div class="offset-lg-3 col-lg-6">
    
                    <div class="form-group">
                        <label>Guest Link Expiry Duration</label>
                        <select class="form-control select2 select2-hidden-accessible" name="signed_url_expiry_hours" style="width: 100%;">
                          <option value="24" @if($currentValue == 24) selected="selected" @endif>1 Day (24 hrs)</option>
                          <option value="72" @if($currentValue == 72) selected="selected" @endif>3 Days (72 hrs)</option>
                          <option value="168" @if($currentValue == 168) selected="selected" @endif>7 Days (168 hrs)</option>
                          <option value="720" @if($currentValue == 720) selected="selected" @endif>1 Month (720 hrs)</option>
                        </select>
                    </div>
    
                    <div class="form-group">
                        <a href='{{ route('website.index') }}' class="btn btn-warning">Back</a>
                        <button type="submit" class="btn btn-primary float-right">Save</button>
                    </div>
                </div>
              </div>
            </form>
        </div>
        <!-- /.row -->
      </div>
      <!-- ./card-body -->
      <div class="card-footer">

      </div>
      <!-- /.card-footer -->
    </div>
    <!-- /.card -->
  </div>
