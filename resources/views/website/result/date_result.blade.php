@php
    $websiteService = new \App\Services\WebsiteService();
    $previousDate = $websiteService->getPreviousDay($tournament,$resultDate);
    $shortPigeons = $websiteService->getPreviousDayShortPigeons($tournament,$previousDate);
@endphp

{!! getFirstWinnerLastWinners($tournament,$resultDate,$players) !!}
<div class="table-responsive card" style="overflow:scroll!important;">
    <table class="table table-striped table-bordered table-hover results" id="results">
        {{-- Info:Tournament Table Header--}}
        <thead class="thead-custom">
        <tr>
            <th>Sr</th>
            <th>Picture</th>
            <th>Name</th>
            @for ($i = 0; $i < $tournament->pigeons; $i++)
                <th>Pigeon {{$i+1}}</th>
            @endfor
            <th>Total</th>
        </tr>
        </thead>
        {{-- Info: Touranament Table Body--}}
        <tbody>
        @foreach ($sortedResultAndPlayers as $data)
            @php

                $results = $players->get($data->player_id);
                $player = $tournament->players->where('id',$data->player_id)->first();
                $playerFlyingTime = (!isset($results->first()->start_time))?'':$results->first()->start_time;
                if (!empty($shortPigeons) && $shortPigeons->isNotEmpty()) {
                    $shortPigeonsOfPlayer = $shortPigeons->where('player_id',$data->player_id);
                }
            @endphp
          @if($player)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>
                    <img
                        @if($player->poster)
                            data-src="{{asset('website/profiles/'.$player->poster)}}"
                        @else
                            @if (config('settings.profile_pic_type')==='circle')
                                data-src="{{asset('website/profiles/profile.png')}}"
                            @else
                                data-src="{{asset('website/profiles/profile-square.png')}}"
                            @endif
                        @endif
                        alt="{{$player->name}}"
                        class="profileimg @if(config('settings.profile_pic_type')==='circle') rounded-circle @endif lozad">
                </td>
                <td style="text-align: left!important;white-space: nowrap;">
                    <b>{{ $player->name}}</b>{{$player->phone}}<br>
                    {{ $player->city }}<b><br>
                        @if ($tournament->start_time != $playerFlyingTime && $playerFlyingTime != '' )
                            <small class="text-danger">Yeh Kabooter subha {{substr($playerFlyingTime, 0, 5)}} per udahe
                                gye<small>
                    @endif
                </td>
                @for ($i = 0; $i < $tournament->pigeons; $i++)
                    @php
                        $pigeonTime = $results->where('pigeon_number', $i+1)->first();
                        if(isset($pigeonTime->pigeon_time)){
                            $time = $pigeonTime->pigeon_time;
                            $format = \Carbon\Carbon::createFromTimeString($resultDate.' '.$time);
                            $add = $format->copy()->addMinute(20);
                            $sub = $format->copy()->subMinute(20);
                            $now = \Carbon\Carbon::now();
                            $res = $now->between($add, $sub);
                        }else{
                            $res=false;
                        }

                        $isShort = false;
                        if (!empty($shortPigeonsOfPlayer) && $shortPigeonsOfPlayer->isNotEmpty()) {
                            $isShort = $shortPigeonsOfPlayer->where('pigeon_number', $i+1)->first();
                        }

                    @endphp
                    <td @if($res) class="blink" @endif  @if($isShort)  style="background: #fcf8e3!important;color: #fff" @endif>
                        @php
                            if(isset($pigeonTime->pigeon_time)){ echo $pigeonTime->pigeon_time;}
                        @endphp
                        @if($tournament->allow_double_stamp && isset($pigeonTime->is_double_stamp) && $pigeonTime->is_double_stamp)
                            <br><span class="badge badge-warning" style="background-color: #ffc107; color: #000; font-size: 0.7rem; padding: 0.2rem 0.4rem;">Double stamp</span>
                        @endif
                        @php
                            if(isset($res)){$res=false;}
                        @endphp
                    </td>
                @endfor
                {{--Info: Player Total Time td Start --}}
                @if (isset($data->total))
                    @php
                        $seconds = $data->total ;
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                    @endphp
                    <td>
                        {{sprintf("%02d", $hours)}}:{{sprintf("%02d", $minutes)}}:{{sprintf("%02d", $seconds)}}
                    </td>
                @else
                    <td>

                    </td>
                @endif
            </tr>
                              @endif
        @endforeach
        </tbody>
    </table>
</div>
