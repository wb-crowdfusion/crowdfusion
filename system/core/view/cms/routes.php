<?php

$routes = array (
  '/robots\.txt' => array (
    'view' => 'robots.cft',
    'view_handler' => 'txt'
  ),
  '/(dashboard)?/?' =>
  array (
    'view' => 'dashboard.cft',
  ),
  '/(help)?/?' =>
  array (
    'view' => 'help.cft',
  ),
  '/cache/?' =>
    array (
      'view' => 'cache/info.cft',
  ),
  '/(sites|plugins|cache)(/list)?/?' =>
  array (
    'view' => '$1/list.cft',
  ),
  '/(plugins)/(edit|add)(/(?P<id>[0-9]+))?/?' =>
  array (
    'action' => '$1-$2',
    'action_datasource' => '$1-single',
    'action_form_view' => '$1/edit.cft',
    'action_confirm_delete_view' => 'confirm-delete.cft',
    'action_continue_view' => 'redirect:/$1/edit/$id',
    'action_success_view' => 'redirect:/$1',
    'action_cancel_view' => 'redirect:/$1',
  ),
  '/ajax/get-section/(\\?.+)?' =>
  array (
    'view' => 'sections/$Template.xmod',
    'view_handler' => 'html',
    'view_nodebug' => 'true',
  ),
  '/hotdeploy/(refresh)/?' =>
  array (
    'action' => 'hotdeploy-$1',
  ),
  '/ajax/template/(\\?.+)?' =>
  array (
    'view' => '$Template',
    'view_handler' => 'html',
    'view_nodebug' => 'true',
  ),
  '/ajax/(?P<call>[^/]+)/(\\?.+)?' =>
  array (
    'view' => 'ajax/$call.cft',
    'view_handler' => 'html',
    'view_nodebug' => 'true',
  ),
  '/(?P<Aspect>[a-z0-9-]+)/?' =>
  array (
    'view' => 'node/list.cft',
  ),
  '/(?P<Aspect>[a-z0-9-]+)/(add|edit|duplicate|undelete)/(?P<Element>[a-z0-9-]+)/((?P<OriginalSlug>[a-z0-9-/]+)/)?' =>
  array (
    'action' => 'node-$2',
    'action_datasource' => 'node-single',
    'action_form_view' => 'node/edit.cft',
    'action_continue_view' => 'redirect:/$Aspect/edit/$Element/$slug/',
    'action_success_view' => 'redirect:/$Aspect/',
    'action_cancel_view' => 'redirect:/$Aspect/',
    'action_new_view' => 'redirect:/$Aspect/duplicate/$Element/$newslug/',
    'action_confirm_delete_view' => 'confirm-delete.cft',
  ),
  '/(?P<Aspect>[a-z0-9-]+)/delete/(?P<Element>[a-z0-9-]+)/(?P<Slug>[a-z0-9-/]+)/' =>
  array (
    'action' => 'node-delete',
    'action_datasource' => 'node-single',
    'action_form_view' => 'confirm-delete.cft',
    'action_success_view' => 'redirect:/$Aspect/',
    'action_cancel_view' => 'original_referer',
  ),
  '/bulk/([a-z0-9-/]+)/' =>
  array (
    'action' => 'bulk$1-execute',
    'b_save' => true,
  ),
);
