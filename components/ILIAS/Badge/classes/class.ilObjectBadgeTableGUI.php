<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Badge;

use ILIAS\UI\Factory;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ilLanguage;
use ilGlobalTemplateInterface;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\HTTP\Services;
use Psr\Http\Message\RequestInterface;
use ILIAS\UI\Component\Table\DataRowBuilder;
use Generator;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\URLBuilderToken;
use ilBadge;
use ilBadgeHandler;
use ILIAS\Data\URI;
use ILIAS\UI\Implementation\Component\Link\Standard;
use ilObject;
use ilLink;
use ilObjBadgeAdministrationGUI;
use ILIAS\Filesystem\Stream\Streams;

class ilObjectBadgeTableGUI
{
    private readonly Factory $factory;
    private readonly Renderer $renderer;
    private readonly \ILIAS\Refinery\Factory $refinery;
    private readonly ServerRequestInterface|RequestInterface $request;
    private readonly Services $http;
    private readonly ilLanguage $lng;
    private readonly ilGlobalTemplateInterface $tpl;
    private ilObjBadgeAdministrationGUI $parent_obj;

    public function __construct(ilObjBadgeAdministrationGUI $parentObj)
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();
        $this->http = $DIC->http();
        $this->parent_obj = $parentObj;
    }

    private function buildDataRetrievalObject(
        Factory $f,
        Renderer $r,
        ilObjBadgeAdministrationGUI $p
    ): DataRetrieval {
        return new class ($f, $r, $p) implements DataRetrieval {
            private ilBadgeImage $badge_image_service;
            private Factory $factory;
            private Renderer $renderer;
            private \ilCtrlInterface $ctrl;
            private ilLanguage $lng;
            private \ilAccessHandler $access;
            private ?bool $user_has_write_permission = null;

            public function __construct(
                private Factory $ui_factory,
                private Renderer $ui_renderer,
                private ilObjBadgeAdministrationGUI $parent
            ) {
                global $DIC;

                $this->badge_image_service = new ilBadgeImage(
                    $DIC->resourceStorage(),
                    $DIC->upload(),
                    $DIC->ui()->mainTemplate()
                );
                $this->factory = $this->ui_factory;
                $this->renderer = $this->ui_renderer;
                $this->ctrl = $DIC->ctrl();
                $this->lng = $DIC->language();
                $this->access = $DIC->access();
            }

            private function userHasWritePermission(int $parent_id): bool
            {
                if ($this->user_has_write_permission === null) {
                    $parent_ref_id = ilObject::_getAllReferences($parent_id);
                    if (\count($parent_ref_id) > 0) {
                        $parent_ref_id = array_pop($parent_ref_id);
                    }
                    $this->user_has_write_permission = $this->access->checkAccess('write', '', $parent_ref_id);
                }

                return $this->user_has_write_permission;
            }

            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): Generator {
                $records = $this->getRecords($range, $order);
                foreach ($records as $record) {
                    $row_id = (string) $record['id'];
                    yield $row_builder->buildDataRow($row_id, $record);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                return \count($this->getRecords());
            }

            /**
             * @return list<array{
             *     id: int,
             *     active: bool,
             *     type: string,
             *     image_rid: string,
             *     title: string,
             *     container: string,
             *     container_icon: string,
             *     container_url: string,
             *     container_deleted: bool,
             *     container_id: int,
             *     container_type: string}>
             */
            private function getRecords(Range $range = null, Order $order = null): array
            {
                $data = [];
                $image_html = '';
                $badge_img_large = '';
                $container_icon = '';
                $modal_container = new ModalBuilder();

                $types = ilBadgeHandler::getInstance()->getAvailableTypes(false);
                $filter = ['type' => '', 'title' => '', 'object' => ''];
                foreach (ilBadge::getObjectInstances($filter) as $badge_item) {
                    $type_caption = ilBadge::getExtendedTypeCaption($types[$badge_item['type_id']]);
                    $badge_rid = $badge_item['image_rid'];
                    $image_src = $this->badge_image_service->getImageFromResourceId($badge_item, $badge_rid);
                    if ($badge_rid) {
                        $badge_template_image = $image_src;
                        if ($badge_template_image !== '') {
                            $badge_img = $this->factory->image()->responsive(
                                $badge_template_image,
                                $badge_item['title']
                            );
                            $image_html = $this->renderer->render($badge_img);
                        }
                        $image_html_large = $this->badge_image_service->getImageFromResourceId(
                            $badge_item,
                            $badge_rid,
                            ilBadgeImage::IMAGE_SIZE_XL
                        );
                        if ($image_html_large !== '') {
                            $badge_img_large = $this->ui_factory->image()->responsive(
                                $image_html_large,
                                $badge_item['title']
                            );
                        }
                    }

                    $ref_ids = ilObject::_getAllReferences($badge_item['parent_id']);
                    $ref_id = array_shift($ref_ids);

                    // TODO gvollbach: It seems now the "listObjectBadgeUsers" is now missing in 10.x

                    $container_url_link = '';
                    if ($this->access->checkAccess('read', '', $ref_id)) {
                        $container_url = ilLink::_getLink($ref_id);
                        $container_url_link = $this->renderer->render(
                            new Standard($badge_item['parent_title'], (string) new URI($container_url))
                        );
                        $container_icon = '<img class="ilIcon" src="' .
                            ilObject::_getIcon((int) $badge_item['parent_id'], 'big', $badge_item['parent_type']) .
                            '" alt="' . $this->lng->txt('obj_' . $badge_item['parent_type']) .
                            '" title="' . $this->lng->txt('obj_' . $badge_item['parent_type']) . '" /> ';
                    }

                    $badge_information = [
                        'active' => ($badge_item['active'] ? $this->lng->txt('yes') : $this->lng->txt('no')),
                        'type' => $type_caption,
                        'container' => $container_url_link ?: $badge_item['parent_title'],
                    ];

                    $modal = $modal_container->constructModal(
                        $badge_img_large ?: null,
                        $badge_item['title'],
                        $badge_information
                    );

                    $data[] = [
                        'id' => (int) $badge_item['id'],
                        'active' => (bool) $badge_item['active'],
                        'type' => $type_caption,
                        'image_rid' => $modal_container->renderShyButton(
                            $image_html,
                            $modal
                        ) . ' ' . $modal_container->renderModal($modal),
                        'title' => $modal_container->renderShyButton($badge_item['title'], $modal),
                        'container' => $badge_item['parent_title'],
                        'container_icon' => $container_icon,
                        'container_url' => $container_icon . $container_url_link ?: '',
                        'container_deleted' => ($badge_item['deleted'] ?? false),
                        'container_id' => (int) $badge_item['parent_id'],
                        'container_type' => $badge_item['parent_type'],
                    ];
                }

                if ($order) {
                    [$order_field, $order_direction] = $order->join(
                        [],
                        fn($ret, $key, $value) => [$key, $value]
                    );
                    usort($data, static fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
                    if ($order_field === 'active') {
                        if ($order_direction === 'ASC') {
                            $data = array_reverse($data);
                        }
                    } elseif ($order_direction === 'DESC') {
                        $data = array_reverse($data);
                    }
                }

                if ($range) {
                    $data = \array_slice($data, $range->getStart(), $range->getLength());
                }

                return $data;
            }
        };
    }

    /**
     * @return array<string, \ILIAS\UI\Component\Table\Action\Action>
     */
    private function getActions(
        URLBuilder $url_builder,
        URLBuilderToken $action_parameter_token,
        URLBuilderToken $row_id_token
    ): array {
        $f = $this->factory;

        return [
            'obj_badge_activate' => $f->table()->action()->multi(
                $this->lng->txt('activate'),
                $url_builder->withParameter($action_parameter_token, 'obj_badge_activate'),
                $row_id_token
            ),
            'obj_badge_deactivate' =>
                $f->table()->action()->multi(
                    $this->lng->txt('deactivate'),
                    $url_builder->withParameter($action_parameter_token, 'obj_badge_deactivate'),
                    $row_id_token
                ),
            'obj_badge_delete' =>
                $f->table()->action()->multi(
                    $this->lng->txt('delete'),
                    $url_builder->withParameter($action_parameter_token, 'obj_badge_delete'),
                    $row_id_token
                ),
            'obj_badge_show_users' =>
                $f->table()->action()->single(
                    $this->lng->txt('user'),
                    $url_builder->withParameter($action_parameter_token, 'obj_badge_show_users'),
                    $row_id_token
                )
        ];
    }

    public function renderTable(): void
    {
        $f = $this->factory;
        $r = $this->renderer;
        $refinery = $this->refinery;
        $request = $this->request;
        $df = new \ILIAS\Data\Factory();

        $columns = [
            'image_rid' => $f->table()->column()->text($this->lng->txt('image')),
            'title' => $f->table()->column()->text($this->lng->txt('title')),
            'type' => $f->table()->column()->text($this->lng->txt('type')),
            'container_url' => $f->table()->column()->text($this->lng->txt('container')),
            'active' => $f->table()->column()->boolean(
                $this->lng->txt('active'),
                $this->lng->txt('yes'),
                $this->lng->txt(
                    'no'
                )
            ),
        ];

        $table_uri = $df->uri($request->getUri()->__toString());
        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ['tid'];

        [$url_builder, $action_parameter_token, $row_id_token] =
            $url_builder->acquireParameters(
                $query_params_namespace,
                'table_action',
                'id'
            );

        $data_retrieval = $this->buildDataRetrievalObject($f, $r, $this->parent_obj);

        $actions = $this->getActions($url_builder, $action_parameter_token, $row_id_token);

        $table = $f->table()
                   ->data('', $columns, $data_retrieval)
                   ->withActions($actions)
                   ->withRequest($request);

        $out = [$table];

        $query = $this->http->wrapper()->query();
        if ($query->has($action_parameter_token->getName())) {
            $action = $query->retrieve($action_parameter_token->getName(), $refinery->to()->string());
            $ids = $query->retrieve($row_id_token->getName(), $refinery->custom()->transformation(fn($v) => $v));

            if ($action === 'obj_badge_delete') {
                $items = [];
                if (\is_array($ids) && \count($ids) > 0) {
                    foreach ($ids as $id) {
                        $badge = new ilBadge($id);
                        $items[] = $f->modal()->interruptiveItem()->keyValue(
                            $id,
                            (string) $badge->getId(),
                            $badge->getTitle()
                        );
                    }

                    $this->http->saveResponse(
                        $this->http
                            ->response()
                            ->withBody(
                                Streams::ofString($r->renderAsync([
                                    $f->modal()->interruptive(
                                        $this->lng->txt('badge_deletion'),
                                        $this->lng->txt('badge_deletion_confirmation'),
                                        '#'
                                    )->withAffectedItems($items)
                                ]))
                            )
                    );
                    $this->http->sendResponse();
                    $this->http->close();
                }
            }
        }

        $this->tpl->setContent($r->render($out));
    }
}
