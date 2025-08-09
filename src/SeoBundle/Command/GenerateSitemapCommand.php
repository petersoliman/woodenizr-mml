<?php

namespace App\SeoBundle\Command;

use App\SeoBundle\Service\GenerateSitemapService;
use PN\SeoBundle\Lib\SitemapGenerator;
use PN\ServiceBundle\Lib\UploadPath;
use PN\ServiceBundle\Service\ContainerParameterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSitemapCommand extends Command
{
    protected static $defaultName = 'app:generate-sitemap';

    private ContainerParameterService $containerParameterService;
    private GenerateSitemapService $generateSitemapService;

    public function __construct(
        GenerateSitemapService $generateSitemapService,
        ContainerParameterService $containerParameterService
    ) {
        parent::__construct();
        $this->containerParameterService = $containerParameterService;
        $this->generateSitemapService = $generateSitemapService;
    }

    protected function configure()
    {
        $this
            ->setDescription("Generate Sitemap")
            ->setHelp("Run this command weekly");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validateParameters($output);

        $pages = $this->generateSitemapService->generate();

        $sitemapGenerator = new SitemapGenerator($this->getDomain());

        $sitemapGenerator->setPath(UploadPath::getRootDir());
        // $sitemapGenerator->setFileName();
        $firstPage = true;
        foreach ($pages as $page) {
            $url = $page['url'];
            $lastModifiedDate = $page['lastModifiedDate'];
            $priority = $firstPage ? "1.0" : SitemapGenerator::DEFAULT_PRIORITY;
            $lastmod = $lastModifiedDate->format("c");

            $sitemapGenerator->addItem($url, $priority, null, $lastmod);
            $firstPage = false;
        }
        $sitemapGenerator->createSitemapIndex();

        $output->writeln(["<info>The sitemap is generated successfully</info>"]);

        return 0;
    }

    private function getDomain(): string
    {
        return $this->containerParameterService->get("default_uri");

    }

    private function validateParameters(OutputInterface $output)
    {
        if (!$this->containerParameterService->has("default_uri")) {
            $output->writeln(["<error>Please add this \"default_uri\"parameter in your \"parameters.yml\"</error>"]);
            exit;
        }
    }

}