<?php declare(strict_types=1);

namespace Cicada\Core\System\StateMachine\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\StateMachineException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class UnnecessaryTransitionException extends StateMachineException
{
    public function __construct(string $transition)
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            self::UNNECESSARY_TRANSITION,
            'The transition "{{ transition }}" is unnecessary, already on desired state.',
            ['transition' => $transition]
        );
    }
}
