<?php

declare(strict_types=1);

namespace Idiosyncratic\AmpRoute;

use Amp\Http\Server\ServerObserver;

interface TestServerObserverDispatcher extends Dispatcher, ServerObserver
{
}
