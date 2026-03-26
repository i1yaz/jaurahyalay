<div class="table-responsive card" style="overflow:scroll!important;">
<table class="table table-striped table-bordered table-hover results" id="results">
          {{-- Info:Tournament Table Header--}}
          <thead  class="thead-custom">
            <tr>
                <th>Sr</th>
                <th>Picture</th>
                <th>Name</th>
                <th>Pigeons</th>
                @foreach ($tournament->flyingDays as $day)
                <th>{{$day->date}}</th>
                @endforeach
                <th>Total</th>
            </tr>
          </thead>
          {{-- Info: Touranament Table Body--}}
          <tbody>
              @foreach ($sortedResultAndPlayers as $data)
              @php
                $sum=0;
                $results = $players->get($data->player_id);
                $player = $tournament->players->where('id',$data->player_id)->first();
                $playerDateResult = $players->get($data->player_id);
              @endphp
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
                        class="profileimg  @if(config('settings.profile_pic_type')==='circle') rounded-circle @endif lozad">
                </td>
                <td style="text-align: left!important; white-space: nowrap;">
                    <b>{{ $player->name }}</b><br><small>{{ $player->city }}</small>
                </td>
                <td>{{$playerDateResult->sum('double_stamp_landed')}}</td>
                {{--Info: Player Datewise result --}}
                @foreach ($tournament->flyingDays as $tournamentDate)
                        @php
                            $toDayResult = $playerDateResult->where('date',$tournamentDate->date)->first();
                        @endphp
                    @if ($toDayResult != null && $toDayResult->double_stamp_total > 0)
                        @php
                            $sum = $sum + $toDayResult->double_stamp_total;
                            $seconds = $toDayResult->double_stamp_total;
                            $hours = floor($seconds / 3600);
                            $seconds -= $hours * 3600;
                            $minutes = floor($seconds / 60);
                            $seconds -= $minutes * 60;
                        @endphp
                        <td>{{sprintf("%02d", $hours)}}:{{sprintf("%02d", $minutes)}}:{{sprintf("%02d", $seconds)}}</td>
                    @else
                    <td>00:00:00</td>
                    @endif
                @endforeach
                {{--Info: Player Total Time --}}
                @php
                    $seconds = $sum;
                    $hours = floor($seconds / 3600);
                    $seconds -= $hours * 3600;
                    $minutes = floor($seconds / 60);
                    $seconds -= $minutes * 60;
                @endphp
                <td>
                    <b>
                        {{sprintf("%02d", $hours)}}:{{sprintf("%02d", $minutes)}}:{{sprintf("%02d", $seconds)}}
                    </b>
                </td>
            </tr>
              @endforeach
          </tbody>
        </table>
</div>
