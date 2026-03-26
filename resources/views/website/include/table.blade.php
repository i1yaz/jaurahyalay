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

                    <div class="bs-callout bs-callout-info">
                        <p>
                            Lofts:{{$tournament->players->count()}},
                            Total pigeons:{{$tournament->players->count() * $tournament->pigeons}},
                            Pigeons
                            landed:@php echo $tournament->tournamentResult->where('pigeon_time','!=',NULL)->where('pigeon_time','!=','00:00:00')->count();  @endphp
                            ,
                            Pigeons
                            remaining: @php echo ($tournament->players->count() * $tournament->pigeons) - ($tournament->tournamentResult->where('pigeon_time','!=',NULL)->where('pigeon_time','!=','00:00:00')->count()) @endphp
                        </p>
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
