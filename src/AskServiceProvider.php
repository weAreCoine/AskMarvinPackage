<?php

namespace Marvin\Ask;

use League\CommonMark\MarkdownConverter;
use Marvin\Ask\Abstracts\AbstractEmailClientFactory;
use Marvin\Ask\Abstracts\AbstractTracingClient;
use Marvin\Ask\Abstracts\AudioFileTranscoder;
use Marvin\Ask\Abstracts\LlmProviderClient;
use Marvin\Ask\Abstracts\VectorialDatabaseClient;
use Marvin\Ask\Actions\FfmpegVoiceChatMessageConverter;
use Marvin\Ask\Clients\LangfuseClient;
use Marvin\Ask\Clients\PineconeClient;
use Marvin\Ask\Clients\PrismClient;
use Marvin\Ask\Clients\WhatsAppClient;
use Marvin\Ask\Commands\AskQuestion;
use Marvin\Ask\Commands\DeleteOldCommandRuns;
use Marvin\Ask\Commands\Documents\DeleteOrphans;
use Marvin\Ask\Commands\GetEmbedVector;
use Marvin\Ask\Delegates\MarkdownConverterBindingDelegate;
use Marvin\Ask\Factories\GmailClientFactory;
use Marvin\Ask\Repositories\PromptRepository;
use Marvin\Ask\Services\LlmService;
use Marvin\Ask\Services\MarkdownConversionService;
use Marvin\Ask\Services\TracingContextService;
use Marvin\Ask\Services\VectorialDatabaseService;
use Marvin\Ask\Support\TokenCounter;
use Prism\Prism\Prism;
use Probots\Pinecone\Client as Pinecone;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('ask')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasCommands([
                DeleteOrphans::class,
                AskQuestion::class,
                DeleteOldCommandRuns::class,
                GetEmbedVector::class,
            ]);
    }

    public function register()
    {
        parent::register();
        $this->registerBindings();
        $this->registerScopedServices();
        $this->registerSingletonServices();

        return $this;
    }

    /**
     * Register all the application bindings for dependency injection.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(VectorialDatabaseClient::class, PineconeClient::class);
        $this->app->bind(MarkdownConverter::class, fn () => MarkdownConverterBindingDelegate::getConcrete());
        $this->app->bind(AbstractEmailClientFactory::class, GmailClientFactory::class);
    }

    /**
     * Register any application scoped services.
     * Scoped services are created for each request and are destroyed when the request is complete even
     * if the application uses long-running processes (e.g., Octane).
     */
    protected function registerScopedServices(): void
    {
        $this->app->scoped(LlmProviderClient::class, PrismClient::class);
        $this->app->scoped(TracingContextService::class, TracingContextService::class);
    }

    /**
     * Register any application singleton services.
     * Singletons can survive application restarts if it uses some long-running resources (e.g., Octane).
     *
     * Note that Singletons are bound to the container, so they can be resolved using the DependencyInjection
     * container. Basically app->singleton = app->bind with true as the third parameter (shared).
     */
    protected function registerSingletonServices(): void
    {
        $this->app->singleton(TokenCounter::class, fn () => new TokenCounter(config('ask.services.prism.chat.model')));
        $this->app->singleton(Prism::class, Prism::class);
        $this->app->singleton(Pinecone::class,
            fn () => new Pinecone(config('ask.services.pinecone.api_key'),
                config('ask.services.pinecone.index_host')));

        $this->app->singleton(PromptRepository::class);
        $this->app->singleton(MarkdownConversionService::class);
        $this->app->singleton(VectorialDatabaseService::class);
        $this->app->singleton(AudioFileTranscoder::class, FfmpegVoiceChatMessageConverter::class);
        $this->app->singleton(WhatsAppClient::class);
        $this->app->singleton(LlmService::class);
        $this->app->singleton(AbstractTracingClient::class,
            fn () => new LangfuseClient(
                config('ask.services.langfuse.key'),
                config('ask.services.langfuse.secret'),
                config('ask.services.langfuse.host')
            )
        );
    }
}
