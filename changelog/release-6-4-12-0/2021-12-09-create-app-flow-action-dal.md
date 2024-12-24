---
title: Create app flow action DAL
issue: NEXT-18950
---
# Core
*  Added migration to create two new tables `app_flow_action` and `app_flow_action_translation`.
*  Added entities, definition and collection for table `app_flow_action` at `Cicada\Core\Framework\App\Aggregate\FlowAction`.
*  Added entities, definition and collection for table `app_flow_action_translation` at `Cicada\Core\Framework\App\Aggregate\FlowActionTranslation`.
*  Added OneToMany association between `app` and `app_flow_action`.
*  Added new property `flowActions` to `Cicada\Core\Framework\App\AppEntity`.
*  Added OneToMany association between `language` and `app_flow_action_translation`.
*  Added new property `appFlowActionTranslations` to `Cicada\Core\System\Language\LanguageEntity`.
*  Added new class `Cicada\Core\Framework\App\FlowAction\AppFlowActionLoadedSubscriber`
