<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * @phpstan-import-type XmlContainerArray from XmlContainer
 */
class XmlDocument
{
    public function __construct(
        protected readonly string $version,
        protected readonly ?string $encoding = null,
        protected readonly ?XmlContainer $root = null,
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function getRoot(): ?XmlContainer
    {
        return $this->root;
    }

    /**
     * @return array{version: string, encoding: ?string, root: ?XmlContainerArray}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'root' => $this->root?->toArray(),
        ];
    }
}
