<?php

/*
<COPYRIGHT>

    Copyright © 2022-2023, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace Assist\LaravelAuditing;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Assist\LaravelAuditing\Contracts\Resolver;
use Assist\LaravelAuditing\Events\AuditCustom;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Assist\LaravelAuditing\Contracts\AttributeEncoder;
use Assist\LaravelAuditing\Contracts\AttributeRedactor;
use Assist\LaravelAuditing\Exceptions\AuditingException;
use Assist\LaravelAuditing\Exceptions\AuditableTransitionException;

trait Auditable
{
    /**
     * Auditable attributes excluded from the Audit.
     *
     * @var array
     */
    protected $excludedAttributes = [];

    /**
     * Audit event name.
     *
     * @var string
     */
    public $auditEvent;

    /**
     * Is auditing disabled?
     *
     * @var bool
     */
    public static $auditingDisabled = false;

    /**
     * Property may set custom event data to register
     *
     * @var null|array
     */
    public $auditCustomOld = null;

    /**
     * Property may set custom event data to register
     *
     * @var null|array
     */
    public $auditCustomNew = null;

    /**
     * If this is a custom event (as opposed to an eloquent event
     *
     * @var bool
     */
    public $isCustomEvent = false;

    /**
     * @var array Preloaded data to be used by resolvers
     */
    public $preloadedResolverData = [];

    /**
     * Auditable boot logic.
     *
     * @return void
     */
    public static function bootAuditable()
    {
        if (static::isAuditingEnabled()) {
            static::observe(new AuditableObserver());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(
            Config::get('audit.implementation', Models\Audit::class),
            'auditable'
        );
    }

    /**
     * @return array
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? Config::get('audit.exclude', []);
    }

    /**
     * @return array
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function readyForAuditing(): bool
    {
        if (static::$auditingDisabled) {
            return false;
        }

        if ($this->isCustomEvent) {
            return true;
        }

        return $this->isEventAuditable($this->auditEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function toAudit(): array
    {
        if (! $this->readyForAuditing()) {
            throw new AuditingException('A valid audit event has not been set');
        }

        $attributeGetter = $this->resolveAttributeGetter($this->auditEvent);

        if (! method_exists($this, $attributeGetter)) {
            throw new AuditingException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->auditEvent,
                $attributeGetter
            ));
        }

        $this->resolveAuditExclusions();

        [$old, $new] = $this->$attributeGetter();

        if ($this->getAttributeModifiers() && ! $this->isCustomEvent) {
            foreach ($old as $attribute => $value) {
                $old[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }

            foreach ($new as $attribute => $value) {
                $new[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }
        }

        $morphPrefix = Config::get('audit.user.morph_prefix', 'user');

        $tags = implode(',', $this->generateTags());

        $user = $this->resolveUser();

        return $this->transformAudit(array_merge([
            'old_values' => $old,
            'new_values' => $new,
            'event' => $this->auditEvent,
            'auditable_id' => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            $morphPrefix . '_id' => $user ? $user->getAuthIdentifier() : null,
            $morphPrefix . '_type' => $user ? $user->getMorphClass() : null,
            'tags' => empty($tags) ? null : $tags,
        ], $this->runResolvers()));
    }

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        return $data;
    }

    public function preloadResolverData()
    {
        $this->preloadedResolverData = $this->runResolvers();

        if (! empty($this->resolveUser())) {
            $this->preloadedResolverData['user'] = $this->resolveUser();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuditEvent(string $event): Contracts\Auditable
    {
        $this->auditEvent = $this->isEventAuditable($event) ? $event : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvent()
    {
        return $this->auditEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvents(): array
    {
        return $this->auditEvents ?? Config::get('audit.events', [
            'created',
            'updated',
            'deleted',
            'restored',
        ]);
    }

    /**
     * Disable Auditing.
     *
     * @return void
     */
    public static function disableAuditing()
    {
        static::$auditingDisabled = true;
    }

    /**
     * Enable Auditing.
     *
     * @return void
     */
    public static function enableAuditing()
    {
        static::$auditingDisabled = false;
    }

    /**
     * Determine whether auditing is enabled.
     *
     * @return bool
     */
    public static function isAuditingEnabled(): bool
    {
        if (App::runningInConsole()) {
            return Config::get('audit.enabled', true) && Config::get('audit.console', false);
        }

        return Config::get('audit.enabled', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditStrict(): bool
    {
        return $this->auditStrict ?? Config::get('audit.strict', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditTimestamps(): bool
    {
        return $this->auditTimestamps ?? Config::get('audit.timestamps', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditDriver()
    {
        return $this->auditDriver ?? Config::get('audit.driver', 'database');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditThreshold(): int
    {
        return $this->auditThreshold ?? Config::get('audit.threshold', 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeModifiers(): array
    {
        return $this->attributeModifiers ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function generateTags(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function transitionTo(Contracts\Audit $audit, bool $old = false): Contracts\Auditable
    {
        // The Audit must be for an Auditable model of this type
        if ($this->getMorphClass() !== $audit->auditable_type) {
            throw new AuditableTransitionException(sprintf(
                'Expected Auditable type %s, got %s instead',
                $this->getMorphClass(),
                $audit->auditable_type
            ));
        }

        // The Audit must be for this specific Auditable model
        if ($this->getKey() !== $audit->auditable_id) {
            throw new AuditableTransitionException(sprintf(
                'Expected Auditable id (%s)%s, got (%s)%s instead',
                gettype($this->getKey()),
                $this->getKey(),
                gettype($audit->auditable_id),
                $audit->auditable_id
            ));
        }

        // Redacted data should not be used when transitioning states
        foreach ($this->getAttributeModifiers() as $attribute => $modifier) {
            if (is_subclass_of($modifier, AttributeRedactor::class)) {
                throw new AuditableTransitionException('Cannot transition states when an AttributeRedactor is set');
            }
        }

        // The attribute compatibility between the Audit and the Auditable model must be met
        $modified = $audit->getModified();

        if ($incompatibilities = array_diff_key($modified, $this->getAttributes())) {
            throw new AuditableTransitionException(sprintf(
                'Incompatibility between [%s:%s] and [%s:%s]',
                $this->getMorphClass(),
                $this->getKey(),
                get_class($audit),
                $audit->getKey()
            ), array_keys($incompatibilities));
        }

        $key = $old ? 'old' : 'new';

        foreach ($modified as $attribute => $value) {
            if (array_key_exists($key, $value)) {
                $this->setAttribute($attribute, $value[$key]);
            }
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Pivot help methods
    |--------------------------------------------------------------------------
    |
    | Methods for auditing pivot actions
    |
    */

    /**
     * @param string $relationName
     * @param mixed $id
     * @param array $attributes
     * @param bool $touch
     * @param array $columns
     *
     * @throws AuditingException
     *
     * @return void
     */
    public function auditAttach(string $relationName, $id, array $attributes = [], $touch = true, $columns = ['*'])
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'attach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method attach');
        }

        $old = $this->{$relationName}()->get($columns);
        $this->{$relationName}()->attach($id, $attributes, $touch);
        $new = $this->{$relationName}()->get($columns);
        $this->dispatchRelationAuditEvent($relationName, 'attach', $old, $new);
    }

    /**
     * @param string $relationName
     * @param mixed $ids
     * @param bool $touch
     * @param array $columns
     *
     * @throws AuditingException
     *
     * @return int
     */
    public function auditDetach(string $relationName, $ids = null, $touch = true, $columns = ['*'])
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'detach')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method detach');
        }

        $old = $this->{$relationName}()->get($columns);
        $results = $this->{$relationName}()->detach($ids, $touch);
        $new = $this->{$relationName}()->get($columns);
        $this->dispatchRelationAuditEvent($relationName, 'detach', $old, $new);

        return empty($results) ? 0 : $results;
    }

    /**
     * @param $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param bool $detaching
     * @param array $columns
     *
     * @throws AuditingException
     *
     * @return array
     */
    public function auditSync($relationName, $ids, $detaching = true, $columns = ['*'])
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'sync')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method sync');
        }

        $old = $this->{$relationName}()->get($columns);
        $changes = $this->{$relationName}()->sync($ids, $detaching);

        if (collect($changes)->flatten()->isEmpty()) {
            $old = $new = collect([]);
        } else {
            $new = $this->{$relationName}()->get($columns);
        }
        $this->dispatchRelationAuditEvent($relationName, 'sync', $old, $new);

        return $changes;
    }

    /**
     * @param string $relationName
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array $ids
     * @param array $columns
     *
     * @throws AuditingException
     *
     * @return array
     */
    public function auditSyncWithoutDetaching(string $relationName, $ids, $columns = ['*'])
    {
        if (! method_exists($this, $relationName) || ! method_exists($this->{$relationName}(), 'syncWithoutDetaching')) {
            throw new AuditingException('Relationship ' . $relationName . ' was not found or does not support method syncWithoutDetaching');
        }

        return $this->auditSync($relationName, $ids, false, $columns);
    }

    /**
     * Resolve the Auditable attributes to exclude from the Audit.
     *
     * @return void
     */
    protected function resolveAuditExclusions()
    {
        $this->excludedAttributes = $this->getAuditExclude();

        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->getAuditStrict()) {
            // Hidden attributes
            $this->excludedAttributes = array_merge($this->excludedAttributes, $this->hidden);

            // Non visible attributes
            if ($this->visible) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);

                $this->excludedAttributes = array_merge($this->excludedAttributes, $invisible);
            }
        }

        // Exclude Timestamps
        if (! $this->getAuditTimestamps()) {
            if ($this->getCreatedAtColumn()) {
                $this->excludedAttributes[] = $this->getCreatedAtColumn();
            }

            if ($this->getUpdatedAtColumn()) {
                $this->excludedAttributes[] = $this->getUpdatedAtColumn();
            }

            if (method_exists($this, 'getDeletedAtColumn')) {
                $this->excludedAttributes[] = $this->getDeletedAtColumn();
            }
        }

        // Valid attributes are all those that made it out of the exclusion array
        $attributes = Arr::except($this->attributes, $this->excludedAttributes);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (
                is_array($value) ||
                (is_object($value) &&
                    ! method_exists($value, '__toString') &&
                    ! ($value instanceof \UnitEnum))
            ) {
                $this->excludedAttributes[] = $attribute;
            }
        }
    }

    /**
     * Get the old/new attributes of a retrieved event.
     *
     * @return array
     */
    protected function getRetrievedEventAttributes(): array
    {
        // This is a read event with no attribute changes,
        // only metadata will be stored in the Audit

        return [
            [],
            [],
        ];
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    protected function getCreatedEventAttributes(): array
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return [
            [],
            $new,
        ];
    }

    protected function getCustomEventAttributes(): array
    {
        return [
            $this->auditCustomOld,
            $this->auditCustomNew,
        ];
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return array
     */
    protected function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return [
            $old,
            $new,
        ];
    }

    /**
     * Get the old/new attributes of a deleted event.
     *
     * @return array
     */
    protected function getDeletedEventAttributes(): array
    {
        $old = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = $value;
            }
        }

        return [
            $old,
            [],
        ];
    }

    /**
     * Get the old/new attributes of a restored event.
     *
     * @return array
     */
    protected function getRestoredEventAttributes(): array
    {
        // A restored event is just a deleted event in reverse
        return array_reverse($this->getDeletedEventAttributes());
    }

    /**
     * Modify attribute value.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @throws AuditingException
     *
     * @return mixed
     *
     */
    protected function modifyAttributeValue(string $attribute, $value)
    {
        $attributeModifiers = $this->getAttributeModifiers();

        if (! array_key_exists($attribute, $attributeModifiers)) {
            return $value;
        }

        $attributeModifier = $attributeModifiers[$attribute];

        if (is_subclass_of($attributeModifier, AttributeRedactor::class)) {
            return call_user_func([$attributeModifier, 'redact'], $value);
        }

        if (is_subclass_of($attributeModifier, AttributeEncoder::class)) {
            return call_user_func([$attributeModifier, 'encode'], $value);
        }

        throw new AuditingException(sprintf('Invalid AttributeModifier implementation: %s', $attributeModifier));
    }

    /**
     * Resolve the User.
     *
     * @throws AuditingException
     *
     * @return mixed|null
     *
     */
    protected function resolveUser()
    {
        $userResolver = Config::get('audit.user.resolver');

        if (is_null($userResolver) && Config::has('audit.resolver') && ! Config::has('audit.user.resolver')) {
            trigger_error(
                'The config file audit.php is not updated to the new version 13.0. Please see https://laravel-auditing.com/guide/upgrading.html',
                E_USER_DEPRECATED
            );
            $userResolver = Config::get('audit.resolver.user');
        }

        if (is_subclass_of($userResolver, \Assist\LaravelAuditing\Contracts\UserResolver::class)) {
            return call_user_func([$userResolver, 'resolve'], $this);
        }

        throw new AuditingException('Invalid UserResolver implementation');
    }

    protected function runResolvers(): array
    {
        $resolved = [];
        $resolvers = Config::get('audit.resolvers', []);

        if (empty($resolvers) && Config::has('audit.resolver')) {
            trigger_error(
                'The config file audit.php is not updated to the new version 13.0. Please see https://laravel-auditing.com/guide/upgrading.html',
                E_USER_DEPRECATED
            );
            $resolvers = Config::get('audit.resolver', []);
        }

        foreach ($resolvers as $name => $implementation) {
            if (empty($implementation)) {
                continue;
            }

            if (! is_subclass_of($implementation, Resolver::class)) {
                throw new AuditingException('Invalid Resolver implementation for: ' . $name);
            }
            $resolved[$name] = call_user_func([$implementation, 'resolve'], $this);
        }

        return $resolved;
    }

    /**
     * Determine if an attribute is eligible for auditing.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isAttributeAuditable(string $attribute): bool
    {
        // The attribute should not be audited
        if (in_array($attribute, $this->excludedAttributes, true)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        $include = $this->getAuditInclude();

        return empty($include) || in_array($attribute, $include, true);
    }

    /**
     * Determine whether an event is auditable.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function isEventAuditable($event): bool
    {
        return is_string($this->resolveAttributeGetter($event));
    }

    /**
     * Attribute getter method resolver.
     *
     * @param string $event
     *
     * @return string|null
     */
    protected function resolveAttributeGetter($event)
    {
        if (empty($event)) {
            return;
        }

        if ($this->isCustomEvent) {
            return 'getCustomEventAttributes';
        }

        foreach ($this->getAuditEvents() as $key => $value) {
            $auditableEvent = is_int($key) ? $value : $key;

            $auditableEventRegex = sprintf('/%s/', preg_replace('/\*+/', '.*', $auditableEvent));

            if (preg_match($auditableEventRegex, $event)) {
                return is_int($key) ? sprintf('get%sEventAttributes', ucfirst($event)) : $value;
            }
        }
    }

    /**
     * @param string $relationName
     * @param string $event
     * @param \Illuminate\Support\Collection $old
     * @param \Illuminate\Support\Collection $new
     *
     * @return void
     */
    private function dispatchRelationAuditEvent($relationName, $event, $old, $new)
    {
        $this->auditCustomOld[$relationName] = $old->diff($new)->toArray();
        $this->auditCustomNew[$relationName] = $new->diff($old)->toArray();

        if (
            empty($this->auditCustomOld[$relationName]) &&
            empty($this->auditCustomNew[$relationName])
        ) {
            $this->auditCustomOld = $this->auditCustomNew = [];
        }

        $this->auditEvent = $event;
        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
        $this->isCustomEvent = false;
    }
}
