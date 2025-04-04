<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Attachment;
use App\Policies\AttachmentPolicy;
use App\Models\Meeting;
use App\Policies\MeetingPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(Attachment::class, function ($app) {
            return new Attachment();
        });

        $this->app->bind(AttachmentPolicy::class, function ($app) {
            return new AttachmentPolicy();
        });

        $this->app->bind(Meeting::class, function ($app) {
            return new Meeting();
        });

        $this->app->bind(MeetingPolicy::class, function ($app) {
            return new MeetingPolicy();
        });
    }
} 