<style>
    .news-ticker-wrapper {
        overflow: hidden;
        white-space: nowrap;
        width: 100%;
        position: relative;
    }

    .news-ticker-content {
        display: inline-block;
        white-space: nowrap;
        animation: ticker-scroll 40s linear infinite;
    }

    .news-ticker-content:hover {
        animation-play-state: paused;
    }

    .news-ticker-item {
        display: inline-block;
        padding-left: 3rem;
        font-size: 23px;
        font-weight: bold;
    }

    @keyframes ticker-scroll {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
</style>

<div class="container-fluid">
    @if (isset($activeNews) && $activeNews->isNotEmpty())
    <section class="content-header">
      <div class="row news-ticker">
          <div class="col-lg-12">
              <div class="news-ticker-wrapper mt-2 pb-2" style="line-height: 1.8; padding-top: 8px;">
                  <div class="news-ticker-content">
                    @foreach ($activeNews as $news)
                        <div class="news-ticker-item">
                            {{$news->name}}
                        </div>
                    @endforeach
                  </div>
              </div>
          </div>
      </div>
    </section>
    @endif
</div>
