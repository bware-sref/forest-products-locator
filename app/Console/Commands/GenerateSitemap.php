<?php

namespace App\Console\Commands;

use App\Models\Mill;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;
use Throwable;

#[Signature('zed:sitemap {outfile=sitemap.xml}')]
#[Description('Generates an XML sitemap and writes it to the specified file.')]
class GenerateSitemap extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * Ensure output file path is presumably relative to ~/public
         */
        $file = public_path($this->argument('outfile'));

        if (empty($file)) {
            $this->error('The argument "outfile" is required. Exiting...');
            return parent::FAILURE;
        }

        $url = config('app.url');

        /**
         * The basic version starts crawling the given $url and follows local links.
         * However, it can't deal with JavaScript, so it doesn't find any individual mill pages.
         * We can still add mills to the site map, but we have to use DB ops instead of crawling the site.
         * Given that, we might want to split the site map into several pieces.
         * At minimum, I'd split regular pages and mills, but it also seems useful to split mills by state.
         * Both are easy enough since we already have to query the DB to include any mill pages.
         * Except...the package includes a contract named Sitemappable that can be used to make models...Sitemappable.
         * 
         * Should we wrap this in a try/catch block?
         * Why not?
         * 
         * Also, I'm not sure if we care about adding last modified to the pages.
         */

        try {
            SitemapGenerator::create($url)
                ->getSitemap()
                ->add(Mill::all())
                ->writeToFile($file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return parent::FAILURE;
        }

        return parent::SUCCESS;
    }
}
