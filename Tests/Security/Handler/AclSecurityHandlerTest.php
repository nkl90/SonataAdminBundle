<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Tests\Security\Handler;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Security\Handler\AclSecurityHandler;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AclSecurityHandlerTest extends TestCase
{
    public function getTokenStorageMock()
    {
        // Set the SecurityContext for Symfony <2.6
        // TODO: Remove conditional return when bumping requirements to SF 2.6+
        if (interface_exists('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')) {
            return $this->getMockForAbstractClass('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
            $this->authorizationChecker = $this->getMockForAbstractClass('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
        }

        return $this->getMockForAbstractClass('Symfony\Component\Security\Core\SecurityContextInterface');
    }

    public function getAuthorizationCheckerMock()
    {
        // Set the SecurityContext for Symfony <2.6
        // TODO: Remove conditional return when bumping requirements to SF 2.6+
        if (interface_exists('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')) {
            return $this->getMockForAbstractClass('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
        }

        return $this->getMockForAbstractClass('Symfony\Component\Security\Core\SecurityContextInterface');
    }

    public function testAcl()
    {
        $admin = $this->getMockForAbstractClass('Sonata\AdminBundle\Admin\AdminInterface');
        $admin->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue('test'));

        $authorizationChecker = $this->getAuthorizationCheckerMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $aclProvider = $this->getMockForAbstractClass('Symfony\Component\Security\Acl\Model\MutableAclProviderInterface');

        $handler = new AclSecurityHandler($this->getTokenStorageMock(), $authorizationChecker, $aclProvider, 'Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder', []);

        $this->assertTrue($handler->isGranted($admin, ['TOTO']));
        $this->assertTrue($handler->isGranted($admin, 'TOTO'));

        $authorizationChecker = $this->getAuthorizationCheckerMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $handler = new AclSecurityHandler($this->getTokenStorageMock(), $authorizationChecker, $aclProvider, 'Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder', []);

        $this->assertFalse($handler->isGranted($admin, ['TOTO']));
        $this->assertFalse($handler->isGranted($admin, 'TOTO'));
    }

    public function testBuildInformation()
    {
        $informations = [
            'EDIT' => ['EDIT'],
        ];

        $authorizationChecker = $this->getAuthorizationCheckerMock();
        $admin = $this->getMockForAbstractClass('Sonata\AdminBundle\Admin\AdminInterface');
        $admin->expects($this->once())
            ->method('getCode')
            ->will($this->returnValue('test'));

        $admin->expects($this->once())
            ->method('getSecurityInformation')
            ->will($this->returnValue($informations));

        $aclProvider = $this->getMockForAbstractClass('Symfony\Component\Security\Acl\Model\MutableAclProviderInterface');

        $handler = new AclSecurityHandler($this->getTokenStorageMock(), $authorizationChecker, $aclProvider, 'Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder', []);

        $results = $handler->buildSecurityInformation($admin);

        $this->assertArrayHasKey('ROLE_TEST_EDIT', $results);
    }

    public function testWithAuthenticationCredentialsNotFoundException()
    {
        $admin = $this->getMockForAbstractClass('Sonata\AdminBundle\Admin\AdminInterface');

        $authorizationChecker = $this->getAuthorizationCheckerMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->throwException(new AuthenticationCredentialsNotFoundException('FAIL')));

        $aclProvider = $this->getMockForAbstractClass('Symfony\Component\Security\Acl\Model\MutableAclProviderInterface');

        $handler = new AclSecurityHandler($this->getTokenStorageMock(), $authorizationChecker, $aclProvider, 'Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder', []);

        $this->assertFalse($handler->isGranted($admin, 'raise exception', $admin));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWithNonAuthenticationCredentialsNotFoundException()
    {
        $admin = $this->getMockForAbstractClass('Sonata\AdminBundle\Admin\AdminInterface');

        $authorizationChecker = $this->getAuthorizationCheckerMock();
        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->throwException(new \RuntimeException('FAIL')));

        $aclProvider = $this->getMockForAbstractClass('Symfony\Component\Security\Acl\Model\MutableAclProviderInterface');

        $handler = new AclSecurityHandler($this->getTokenStorageMock(), $authorizationChecker, $aclProvider, 'Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder', []);

        $this->assertFalse($handler->isGranted($admin, 'raise exception', $admin));
    }
}
