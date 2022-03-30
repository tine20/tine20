<?php declare(strict_types=1);

class HumanResources_Config_AttendanceRecorder
{
    public const METADATA_SOURCE = 'source';

    protected $account;
    protected $device;
    protected $employee;
    protected $freetimetypeId;
    protected $metaData;
    protected $reason;
    protected $refId;
    protected $status;
    protected $throwOnFaultyAction = false;
    protected $timeStamp;

    public function setAccount(?Tinebase_Model_FullUser $user): self
    {
        $this->account = $user;
        return $this;
    }

    public function getAccount(): ?Tinebase_Model_FullUser
    {
        return $this->account;
    }

    public function setDevice(?HumanResources_Model_AttendanceRecorderDevice $device): self
    {
        $this->device = $device;
        return $this;
    }

    public function getDevice(): ?HumanResources_Model_AttendanceRecorderDevice
    {
        return $this->device;
    }

    public function setEmployee(?HumanResources_Model_Employee $e): self
    {
        $this->employee = $e;
        return $this;
    }

    public function getEmployee(): ?HumanResources_Model_Employee
    {
        return $this->employee;
    }

    public function setFreetimetypeId(?string $id): self
    {
        $this->freetimetypeId = $id;
        return $this;
    }

    public function getFreetimetypeId(): ?string
    {
        return $this->freetimetypeId;
    }

    public function setMetaData(?array $md): self
    {
        $this->metaData = $md;
        return $this;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function setReason(?HumanResources_Model_FreeTimeType $ftt): self
    {
        $this->reason = $ftt;
        return $this;
    }

    public function getReason(): ?HumanResources_Model_FreeTimeType
    {
        return $this->reason;
    }

    public function setRefId(?string $refId): self
    {
        $this->refId = $refId;
        return $this;
    }

    public function getRefId(): ?string
    {
        return $this->refId;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setThrowOnFaultyAction(bool $throw): self
    {
        $this->throwOnFaultyAction = $throw;
        return $this;
    }

    public function getThrowOnFaultyAction(): bool
    {
        return $this->throwOnFaultyAction;
    }

    public function setTimeStamp(?Tinebase_DateTime $tdt): self
    {
        $this->timeStamp = $tdt;
        return $this;
    }

    public function getTimeStamp(): ?Tinebase_DateTime
    {
        return $this->timeStamp;
    }
}
