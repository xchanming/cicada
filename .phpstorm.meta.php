<?php

namespace PHPSTORM_META {
    expectedArguments(
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::setTotalCountMode(),
        0,
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::TOTAL_COUNT_MODE_NONE,
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::TOTAL_COUNT_MODE_EXACT,
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::TOTAL_COUNT_MODE_NEXT_PAGES
    );

    expectedArguments(
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting::__construct(),
        1,
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting::ASCENDING,
        \Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting::DESCENDING
    );

}
