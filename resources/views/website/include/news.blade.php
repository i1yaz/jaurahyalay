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
      <div class="row news-ticker d-flex align-items-center flex-nowrap" style="margin-left: 0; margin-right: 0; padding: 0 15px;">
          <div class="news-label-badge mr-3 d-flex align-items-center">
              <i class="fas fa-bullhorn mr-2 animate-speaker"></i> NEWS
          </div>
          <div class="flex-grow-1 overflow-hidden">
              <div class="news-ticker-wrapper" style="line-height: 1.8; padding: 4px 0;">
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
