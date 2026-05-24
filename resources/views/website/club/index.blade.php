@extends('website.layouts.master')

@section('content')
    <div class="container-fluid content">
        <div class="card card-primary card-tabs">
            {{-- Info:Tournament Tab header --}}
            <div class="card-header  shadow-lg text-light">
                <h3>{{ $club->name}}</h3>
            </div>
            {{-- Tournament Detail --}}
            <div class="card-body">
                <div class="tournament-list">
                    @foreach ($tournaments as $tournament)
                        @php
                            $currentTournamentResult = $tournamentsPositions->only($tournament->id)->first();
                            $startDate = \Carbon\Carbon::parse($tournament->flyingDays->first()->date);
                            $startDate = $startDate->settings(['toStringFormat' => ' j F, Y']);
                            $lastDate = \Carbon\Carbon::parse($tournament->flyingDays->last()->date);
                            $lastDate = $lastDate->settings(['toStringFormat' => ' j F, Y']);
                        @endphp
                        <div class="row no-gutters mb-4 shadow-sm border rounded @if($loop->odd) bg-white @else bg-light @endif">
                            <div class="col-md-3 text-center d-flex align-items-center justify-content-center p-2">
                                <a href="{{route('result.tournament', ['tournament_id' => $tournament->id])}}"
                                    title="results of {{$tournament->name}}">
                                    <img @if($tournament->poster) src="{{asset('uploads/' . $tournament->poster)}}" @else
                                    src="{{asset('website/img/200x250.png')}}" @endif alt="{{$tournament->name}}"
                                        class="img-thumbnail img-responsive poster shadow-sm"
                                        style="max-width: 100%; height: auto; max-height: 250px;">
                                </a>
                            </div>
                            <div class="col-md-9 p-3">
                                <a href="{{route('result.tournament', ['tournament_id' => $tournament->id])}}"
                                    title="results of {{$tournament->name}}">
                                    <h4 class="text-primary font-weight-bold">{{$tournament->name}}</h4>
                                </a>
                                <p class="text-muted mb-2"><i class="far fa-calendar-alt mr-1"></i> {{$startDate}} - {{$lastDate}}</p>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px; text-align: center;">Position</th>
                                                <th style="width: 60px; text-align: center;">Pic</th>
                                                <th>Name</th>
                                                <th>City</th>
                                                <th>Total</th>
                                                <th>Prize</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($currentTournamentResult != null)
                                                @foreach ($currentTournamentResult as $player_id => $data)
                                                    @php
                                                        $seconds = $data[2] ?? 0;
                                                        $hours = floor($seconds / 3600);
                                                        $seconds -= $hours * 3600;
                                                        $minutes = floor($seconds / 60);
                                                        $seconds -= $minutes * 60;
                                                    @endphp
                                                    <tr>
                                                        <td style="text-align: center;">{{ $loop->index + 1 }}</td>
                                                        <td style="width: 60px; text-align: center;">
                                                            <img
                                                                @if($data[4])
                                                                    data-src="{{asset('website/profiles/'.$data[4])}}"
                                                                @else
                                                                    @if (config('settings.profile_pic_type')==='circle')
                                                                        data-src="{{asset('website/profiles/profile.png')}}"
                                                                    @else
                                                                        data-src="{{asset('website/profiles/profile-square.png')}}"
                                                                    @endif
                                                                @endif
                                                                alt="{{$data[0]}}"
                                                                class="profileimg @if(config('settings.profile_pic_type')==='circle') rounded-circle @endif lozad"
                                                                style="float: none; display: block; margin: 0 auto;">
                                                        </td>
                                                        <td>{{$data[0]}}</td>
                                                        <td class="city">{{$data[1]}}</td>
                                                        <td class="time">
                                                            {{sprintf("%02d", $hours)}}:{{sprintf("%02d", $minutes)}}
                                                            :{{sprintf("%02d", $seconds)}}</td>
                                                        <td class="prize">{{$data[3]}}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <!-- /.card -->
            <div class="card-footer">
                {{ $tournaments->links() }}
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        const observer = lozad();
        observer.observe();
    </script>
@endpush