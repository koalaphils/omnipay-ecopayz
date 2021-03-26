<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="xml"/>
    <xsl:template match="items">
        <div class="payment-list row" />
    </xsl:template>

    <xsl:template match="item">
        <div class="col-md-3">
            <div class="payment-item">
                <xsl:copy-of select="./node()" />
            </div>
        </div>
    </xsl:template>
</xsl:stylesheet>