<?php

namespace PHPSTORM_META {

	override(\Doctrine\ORM\EntityManagerInterface::find(0), map([
		'' => '@',
	]));
	override(\Doctrine\ORM\EntityManagerInterface::getReference(0), map([
		'' => '@',
	]));
}
