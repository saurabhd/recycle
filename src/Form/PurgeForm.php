<?php
/**
 * @file
 * Contains \Drupal\trash\Form\PurgeForm.
 */

namespace Drupal\trash\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PurgeForm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'purge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to purge "@label"?', ['@label' => $this->entity->label()]);
  }

  /**
    * {@inheritdoc}
    */
   public function getDescription() {
     // TODO: rework this text.
     return $this->t('Purging "@label" from the database should only be done as a last resort when sensitive information has been introduced inadvertently into a database. In clustered or replicated environments it is very difficult to guarantee that a particular purged document has been removed from all replicas.', ['@label' => $this->entity->label()]);
   }

  /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
      return $this->t('Purge');
    }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('trash.entity_list', ['entity_type_id' => $this->entity->getEntityTypeId()]);
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $entity_id = NULL) {
    /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    if (!$this->entity = $storage->loadDeleted($entity_id)) {
      drupal_set_message(t('Unable to load deleted entity.'), 'error');
      return;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $this->entity->getEntityTypeId();
    $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->purge(array($this->entity));

    drupal_set_message(t('The @entity "@label" has been purged.', [
      '@entity' => $this->entity->getEntityType()->get('label'),
      '@label' => $this->entity->label()
    ]));

    $form_state->setRedirect('trash.entity_list', ['entity_type_id' => $entity_type_id]);
  }

}
