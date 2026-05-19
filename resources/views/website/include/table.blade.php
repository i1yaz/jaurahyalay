@if(isset($tournament))
    <div class="card-body" id="custom-tabs-one-tabContent">
        <div class="tab-pane fade active show">
            <div class="submenu">
                {{--Info:Tournament Dates --}}
                <div class="btn-group" style="display: block !important;">
                    @foreach ($tournament->flyingDays as $day)
                        @php
                            $YYYYmmdd = \Carbon\Carbon::parse($day->date);
                            $readableDate = $YYYYmmdd->settings(['toStringFormat' => 'j F,Y']);
                        @endphp
                        <a style="margin-top: 15px"
                           class="btn btn-submenu @if($day->date == $resultDate) active  @endif"
                           href="{{route('result.tournament.date',['club'=> $tournament->club_id,'tournament'=>$tournament->id,'date'=>$day->date])}}">{{$readableDate}}</a>
                    @endforeach
                    <a style="margin-top: 15px" class="btn btn-submenu @if('total' == $resultDate) active  @endif"
                       href="{{route('result.tournament.date',['club'=> $tournament->club_id,'tournament'=>$tournament->id,'date'=>'total'])}}">Total</a>
                    @if($tournament->allow_double_stamp)
                    <a style="margin-top: 15px"
                       class="btn btn-submenu {{$resultDate == 'double-stamp-total' ? 'active' : ''}}" href="{{route('result.tournament.date', [$tournament->club_id, $tournament->id, 'double-stamp-total'])}}">Double Stamp Total</a>
                    @endif
                </div>
                @if ($resultDate !='total' && $resultDate !='double-stamp-total')

                    <div class="row stats-grid-wrapper flex-nowrap mb-4">
                        <!-- Lofts Stat -->
                        <div class="col-3 mb-3">
                            <div class="card stats-card lofts-stats h-100">
                                <div class="card-body d-flex align-items-center justify-content-center p-2 p-md-3 text-center flex-wrap">
                                    <span class="stats-label font-weight-bold mr-1">LOFTS:</span>
                                    <span class="stats-value font-weight-bold">
                                        {{$tournament->players->count()}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Pigeons Stat -->
                        <div class="col-3 mb-3">
                            <div class="card stats-card total-pigeons-stats h-100">
                                <div class="card-body d-flex align-items-center justify-content-center p-2 p-md-3 text-center flex-wrap">
                                    <span class="stats-label font-weight-bold mr-1">TOTAL PIGEONS:</span>
                                    <span class="stats-value font-weight-bold">
                                        {{$tournament->players->count() * $tournament->pigeons}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Landed Pigeons Stat -->
                        <div class="col-3 mb-3">
                            <div class="card stats-card landed-pigeons-stats h-100">
                                <div class="card-body d-flex align-items-center justify-content-center p-2 p-md-3 text-center flex-wrap">
                                    <span class="stats-label font-weight-bold mr-1">LANDED:</span>
                                    <span class="stats-value font-weight-bold">
                                        {{$tournament->tournamentResult->where('pigeon_time','!=',NULL)->where('pigeon_time','!=','00:00:00')->count()}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Remaining Pigeons Stat -->
                        <div class="col-3 mb-3">
                            <div class="card stats-card remaining-pigeons-stats h-100">
                                <div class="card-body d-flex align-items-center justify-content-center p-2 p-md-3 text-center flex-wrap">
                                    <span class="stats-label font-weight-bold mr-1">REMAINING:</span>
                                    <span class="stats-value font-weight-bold">
                                        {{($tournament->players->count() * $tournament->pigeons) - ($tournament->tournamentResult->where('pigeon_time','!=',NULL)->where('pigeon_time','!=','00:00:00')->count())}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
{{--                        @foreach($playersIdWithHighestTime as $player)--}}
{{--                            <p>--}}
{{--                                فرسٹ ونر : {{$player->name}} ( {{$player->city}} ) {{$highestFirstPigeonTime}}--}}
{{--                            </p>--}}
{{--                        @endforeach--}}
{{--                        @foreach($playersIdWithHighestLastPigeonTimeTime as $player)--}}
{{--                            <p>--}}
{{--                                لاسٹ ونر : {{$player->name}} ( {{$player->city}} ) {{$highestLastPigeonTime}}--}}
{{--                            </p>--}}
{{--                        @endforeach--}}

                    </div>
                @endif
            </div>
            {{-- Tournament Table --}}
            @if ($resultDate === 'total')
                @include('website.result.total_result')
            @elseif($resultDate === 'double-stamp-total')
                @include('website.result.double_stamp_total')
            @else
                @include('website.result.date_result')
            @endif
        </div>
    </div>
@endif
