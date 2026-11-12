<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/classes/mail/WithdrawalMailable.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class WithdrawalMailable
 * @ingroup plugins_generic_withdrawResearchPlugin
 *
 * @brief Mailable class for sending withdrawal notifications to editors and authors in OJS 3.5.
 */

namespace APP\plugins\generic\withdrawResearchPlugin\classes\mail;

use PKP\mail\Mailable;

if (class_exists('PKP\mail\Mailable')) {
    class WithdrawalMailable extends Mailable
    {
        // Inherits full mailable capabilities in OJS 3.4/3.5
    }
} else {
    // Fallback stub for static analysis / older versions
    class WithdrawalMailable
    {
        protected array $recipients = [];
        protected array $from = [];
        protected string $subject = '';
        protected string $body = '';

        public function to(string $email, ?string $name = null): self
        {
            $this->recipients[] = ['email' => $email, 'name' => $name];
            return $this;
        }

        public function from(string $email, ?string $name = null): self
        {
            $this->from = ['email' => $email, 'name' => $name];
            return $this;
        }

        public function subject(string $subject): self
        {
            $this->subject = $subject;
            return $this;
        }

        public function body(string $body): self
        {
            $this->body = $body;
            return $this;
        }

        public function getBody(): string
        {
            return $this->body;
        }

        public function getSubject(): string
        {
            return $this->subject;
        }
    }
}
