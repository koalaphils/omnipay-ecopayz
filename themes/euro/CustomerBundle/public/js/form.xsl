<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"/>

    <!-- TODO customize transformation rules 
         syntax recommendation http://www.w3.org/TR/xslt 
    -->
    <xsl:template match="fields/*">
        <div class="col-sm-12">
            <div class="form-group">
                <xsl:apply-templates select="label" />
                <div>
                    <input type="text" id="{id}" name="{full_name}" class="form-control" value="{value}">
                        <xsl:if test="./@required='required'">
                            <xsl:attribute name="required">required</xsl:attribute>
                        </xsl:if>
                    </input>   
                    <span class="help-block">
                        <ul class="list-unstyled"></ul>
                    </span>
                </div>
            </div>
        </div>
    </xsl:template>
    
    <xsl:template match="label">
        <label class="control-label required" for="{id}">
            <xsl:if test="../@required = 'required'">
                <span class="text text-danger">* </span>
            </xsl:if>
            <xsl:value-of select="." />
        </label>
    </xsl:template>
    
    <xsl:template match="fields/switch">
        <div class="col-sm-12">
            <div class='form-group'>
                <label class="control-label required" for="{id}">
                    <span class="text text-danger">*</span>
                    <xsl:value-of select="label" />
                </label>
                <div>
                    <input type="checkbox" id="{id}" name="{full_name}" value="{value}" checked="checked" data-plugin="switchery" data-color="#81c868" data-secondary-color="#f05050" />
                </div>
            </div>
        </div>
        <script>
            $(function(){
                new Switchery($("#<xsl:value-of select="id" />").get(0));
            });
        </script>
    </xsl:template>

</xsl:stylesheet>
