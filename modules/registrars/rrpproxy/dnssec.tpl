<h3>DNSSEC Management</h3>

{if $successful}
    <div class="alert alert-success text-center">
        <p>{$LANG.changessavedsuccessfully}</p>
    </div>
{/if}

{if $error}
    <div class="alert alert-danger text-center">
        <p>{$error}</p>
    </div>
{/if}

<div class="alert alert-info">
    Be sure you know what you are doing here. Any mistake could render your domain unusable!
</div>

<h4>DS Records</h4>
<table class="table table-striped">
    <thead>
    <tr>
        <th style="width:100px;">Key Tag</th>
        <th style="width:100px;">Algorithm</th>
        <th style="width:100px;">Digest Type</th>
        <th>Digest</th>
    </tr>
    </thead>
    <tbody>
    {foreach item=ds from=$dsdata name=dsdata}
        <tr>
            <td>{$ds.keytag}</td>
            <td>{$algOptions[$ds.alg]}</td>
            <td>{$digestOptions[$ds.digesttype]}</td>
            <td>{$ds.digest}</td>
        </tr>
        {foreachelse}
        <tr>
            <td colspan="4">No records</td>
        </tr>
    {/foreach}
    </tbody>
</table>

<h4>KEY Records</h4>
<form method="POST" action="">
    <table class="table table-striped">
        <thead>
        <tr>
            <th style="width:170px;">Flags</th>
            <th style="width:120px;">Protocol</th>
            <th style="width:200px;">Algorithm</th>
            <th>Public Key</th>
        </tr>
        </thead>
        <tbody>
        {foreach item=key from=$ksdata name=ksdata}
            <tr>
                <td>
                    <select name="DNSSEC[{$smarty.foreach.ksdata.index}][flags]" class="form-control">
                        {foreach $flagOptions as $flags => $name}
                            <option value="{$flags}"{if $key.flags eq $flags} selected{/if}>{$name}</option>
                        {/foreach}
                    </select>
                </td>
                <td>
                    <select name="DNSSEC[{$smarty.foreach.ksdata.index}][protocol]" class="form-control">
                        <option value="3">DNSSEC</option>
                    </select>
                </td>
                <td>
                    <select name="DNSSEC[{$smarty.foreach.ksdata.index}][alg]" class="form-control">
                        {foreach $algOptions as $alg => $name}
                            <option value="{$alg}"{if $key.alg eq $alg} selected{/if}>{$name}</option>
                        {/foreach}
                    </select>
                </td>
                <td>
                    <input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index}][pubkey]" value="{$key.pubkey}" />
                </td>
            </tr>
        {/foreach}
        <tr>
            <td>
                <select name="DNSSEC[{$smarty.foreach.ksdata.index+1}][flags]" class="form-control">
                    {foreach $flagOptions as $flags => $name}
                        <option value="{$flags}">{$name}</option>
                    {/foreach}
                </select>
            </td>
            <td>
                <select name="DNSSEC[{$smarty.foreach.ksdata.index+1}][protocol]" class="form-control">
                    <option value="3">DNSSEC</option>
                </select>
            </td>
            <td>
                <select name="DNSSEC[{$smarty.foreach.ksdata.index+1}][alg]" class="form-control">
                    {foreach $algOptions as $alg => $name}
                        <option value="{$alg}">{$name}</option>
                    {/foreach}
                </select>
            </td>
            <td>
                <input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index+1}][pubkey]" value="">
            </td>
        </tr>
        </tbody>
    </table>

    <p class="text-center">
        <input type="submit" value="{$LANG.clientareasavechanges}" class="btn btn-primary">
        <input type="reset" value="{$LANG.clientareacancel}" class="btn btn-default">
    </p>
</form>
