<?php

namespace App\Data\Enums;

enum AuthDriver: string
{
    case GitHubSocialite = 'github-socialite';
    case GoogleSocialite = 'google-socialite';
    case LoginLink = 'login-link';
    case None = 'none';
}
