<?php

namespace App\Console\Commands;

use App\Models\State;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('zed:state-slugs')]
#[Description('Populate states.slug')]
class AddSlugToStates extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $states = State::whereNull(columns: 'slug', boolean: 'and', not: false)->get();

        if (empty($states)) {
            $this->info('No states with null slugs.');
            exit(Command::SUCCESS);
        }

        $updated = 0;

        foreach($states as $state) {
            $state->slug = Str::slug($state->name);
            $state->save();
            $this->info(sprintf('Updated %s with slug "%s".', $state->name, $state->slug));
            $updated++;
        }

        $this->info("Added slugs to $updated states.");
        exit(Command::SUCCESS);
    }
}
