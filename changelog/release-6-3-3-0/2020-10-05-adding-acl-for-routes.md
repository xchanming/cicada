---
title: Adding ACL for Routes
issue: NEXT-10714 
---
# Core
*  Added `Cicada\Core\Framework\Api\Controller\AclController` to provide all core privileges
*  Added `Cicada\Core\Framework\Routing\Annotation\Acl` to `Cicada\Core\System\SystemConfig\Api\SystemConfigController`, `Cicada\Core\Framework\Api\Controller\AclController`, `Cicada\Core\Framework\Api\Controller\CacheController` and `Cicada\Core\Framework\Api\Controller\UserController`
*  Added Event `Cicada\Core\Framework\Api\Acl\Event\AclGetAdditionalPrivilegesEvent`
___
# API
*  Added ACL permission check to protected Routes. A user needs to have admin rights or needs the route privilege to call a protected route.
___
# Administration
*  Added `sw-users-permissions-detailed-additional-permissions` component
*  Added `acl.api.service.js` to get core privileges

