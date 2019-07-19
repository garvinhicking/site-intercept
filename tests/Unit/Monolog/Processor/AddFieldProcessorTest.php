<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Monolog\Processor;

use App\Entity\User;
use App\Monolog\Processor\AddFieldProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Security;

class AddFieldProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function addFieldProcessorAddsFields()
    {
        $subject = new AddFieldProcessor(['foo' => 'bar']);
        $expected = [
            'extra' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame($expected, $subject->__invoke([]));
    }

    /**
     * @test
     */
    public function addFieldProcessorAddsUsernameAndDisplayName()
    {
        /** @var ObjectProphecy|User $user */
        $user = $this->prophesize(User::class);
        /** @var ObjectProphecy|Security $security */
        $security = $this->prophesize(Security::class);
        $security->getUser()->shouldBeCalled()->willReturn($user->reveal());
        $user->getUsername()->shouldBeCalled()->willReturn('myUsername');
        $user->getDisplayName()->shouldBeCalled()->willReturn('myDisplayName');
        $subject = new AddFieldProcessor([ $security->reveal() ]);
        $expected = [
            'extra' => [
                'username' => 'myUsername',
                'userDisplayName' => 'myDisplayName',
            ],
        ];
        $this->assertSame($expected, $subject->__invoke([]));
    }
}
