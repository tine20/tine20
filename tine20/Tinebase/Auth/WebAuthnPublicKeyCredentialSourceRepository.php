<?php declare(strict_types=1);

use Base64Url\Base64Url;
use Webauthn\PublicKeyCredentialSourceRepository;

class Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{

    public function findOneByCredentialId(string $publicKeyCredentialId): ?\Webauthn\PublicKeyCredentialSource
    {
        $publicKeyCredentialId = Base64Url::encode($publicKeyCredentialId);
        // strlen is fine since its base64, so only ascii!
        if (strlen($publicKeyCredentialId) > 255) {
            throw new Tinebase_Exception_UnexpectedValue('publicKeyCredentialId base64 encoded is longer than 255');
        }
        $webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialId]
            ]))->getFirstRecord();
        if (null === $webauthnPublicKey) {
            return null;
        }
        return \Webauthn\PublicKeyCredentialSource::createFromArray(
            $webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA});
    }

    public function findAllForUserEntity(\Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        if (mb_strlen($publicKeyCredentialUserEntity->getId()) > 40) {
            throw new Tinebase_Exception_UnexpectedValue('user entities id is longer than 40');
        }
        $result = [];
        foreach (Tinebase_Controller_WebauthnPublicKey::getInstance()
                ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                    ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialUserEntity->getId()]
                ])) as $webauthnPublicKey) {
            $result[] = \Webauthn\PublicKeyCredentialSource::createFromArray(
                $webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA});
        }

        return $result;
    }

    public function saveCredentialSource(\Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        if (mb_strlen($publicKeyCredentialSource->getUserHandle()) > 40) {
            throw new Tinebase_Exception_UnexpectedValue('user handle is longer than 40');
        }
        $credId = Base64Url::encode($publicKeyCredentialSource->getPublicKeyCredentialId());
        // strlen is fine since its base64, so only ascii!
        if (strlen($credId) > 255) {
            throw new Tinebase_Exception_UnexpectedValue('publicKeyCredentialId base64 encoded is longer than 255');
        }

        if (null === ($webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
            ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $credId],
                ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $publicKeyCredentialSource->getUserHandle()]
            ]))->getFirstRecord())) {

            Tinebase_Controller_WebauthnPublicKey::getInstance()->create(new Tinebase_Model_WebauthnPublicKey([
                Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID => $publicKeyCredentialSource->getUserHandle(),
                Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID => $credId,
                Tinebase_Model_WebauthnPublicKey::FLD_DATA => $publicKeyCredentialSource->jsonSerialize()
            ]));
        } else {
            $webauthnPublicKey->{Tinebase_Model_WebauthnPublicKey::FLD_DATA} = $publicKeyCredentialSource->jsonSerialize();
            $unsetUser = false;
            try {
                if (!Tinebase_Core::getUser()) {
                    $unsetUser = true;
                    Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserById(
                        $publicKeyCredentialSource->getUserHandle()));
                }
                Tinebase_Controller_WebauthnPublicKey::getInstance()->update($webauthnPublicKey);
            } finally {
                if ($unsetUser) {
                    Tinebase_Core::unsetUser();
                }
            }
        }
    }
}
