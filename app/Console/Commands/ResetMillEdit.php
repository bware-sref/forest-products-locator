<?php

namespace App\Console\Commands;

use App\Models\MillEdit;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zed:reset-mill-edit {mill_edit* : Id(s) of MillEdits to reset}')]
#[Description('Resets the status of the specified MillEdits to the pre-review "pending" state.')]
class ResetMillEdit extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $millEditIds = $this->argument('mill_edit');

        if (empty($millEditIds)) {
            $this->error('You must specify at least one MillEdit id.');
            return parent::FAILURE;
        }


        $howManyReset = 0;

        foreach ($millEditIds as $id) {
            try {
                $me = MillEdit::findOrFail($id);
                $reset = $me->resetReviewStatus();

                if (!$reset) {
                    throw new \Exception("Failed to reset MillEdit #{$id}.");
                }

                $howManyReset++;
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->info("Reset {$howManyReset} MillEdits.");

        return parent::SUCCESS;
    }
}
