<?php
/**
 * For this to take effect, we have to mount this file as a volume for the phpmyadmin service in compose.yaml.
 */
$cfg['LoginCookieValidity'] = 86400; // 24 hours in seconds; must be <= session.gc_maxlifetime