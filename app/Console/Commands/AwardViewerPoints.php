<?php

namespace App\Console\Commands;

use App\Services\ViewerPointsAwarder;
use Illuminate\Console\Command;

class AwardViewerPoints extends Command
{
    protected $signature = 'twitch:award-points';

    protected $description = 'Dá pontos aos espectadores vinculados que estão no chat da live';

    public function handle(ViewerPointsAwarder $awarder): int
    {
        $result = $awarder->run();
        $this->info("{$result['status']}: {$result['awarded']} espectador(es) pontuado(s).");

        return in_array($result['status'], ['token_error', 'api_error'], true) ? self::FAILURE : self::SUCCESS;
    }
}
