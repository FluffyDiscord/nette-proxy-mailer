<?php

namespace ProxyMailer\Extension;

use Nette;
use ProxyMailer\Mailer\ProxyMailer;

class ProxyMailerExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
        'endpoint' => null,
        'basic_auth_user_password' => null,
		'host' => null,
		'port' => null,
		'username' => null,
		'password' => null,
		'security' => null,
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

		$mailer = $builder->getDefinition('nette.mailer');
        $mailer->setFactory(ProxyMailer::class, [$config]);
	}
}
