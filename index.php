<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/index.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @brief Wrapper for Author Research Withdrawal Plugin for OJS 3.5.
 */

require_once __DIR__ . '/WithdrawResearchPlugin.php';

return new \APP\plugins\generic\withdrawResearchPlugin\WithdrawResearchPlugin();
