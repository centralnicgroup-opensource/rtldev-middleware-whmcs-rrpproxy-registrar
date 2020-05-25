<h4>DNSSEC Management</h4>
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
    <input type="hidden" name="submit" value="1">
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong class="panel-title">DS Records</strong> <small>can be used as an alternative for all registries, which do not require Key records</small>
        </div>
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
                        <td>{$ds.alg}</td>
                        <td>{$ds.digesttype}</td>
                        <td>{$ds.digest}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    <form method="POST" action="">
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong class="panel-title">KEY Records</strong> <small>can be used anytime and the conversion into DS records is possible</small>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width:100px;">Flags</th>
                    <th style="width:100px;">Protocol</th>
                    <th style="width:100px;">Algorithm</th>
                    <th>Public Key</th>
                </tr>
            </thead>
            <tbody>
                {foreach item=key from=$ksdata name=ksdata}
                    <tr>
                        <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index}][flags]" value="{$key.flags}"></td>
                        <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index}][protocol]" value="{$key.protocol}"></td>
                        <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index}][alg]" value="{$key.alg}"></td>
                        <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index}][pubkey]" value="{$key.pubkey}"></td>
                    </tr>
                {/foreach}
                <tr>
                    <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index+1}][flags]" value=""></td>
                    <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index+1}][protocol]" value=""></td>
                    <td>
                        <select name="DNSSEC[{$smarty.foreach.ksdata.index+1}][alg]" id="alg" class="form-control">
                            <option value="">-</option>
                            <option value="3">3 - DSA</option>
                            <option value="5">5 - RSASHA1</option>
                            <option value="6">6 - DSA-NSEC3-SHA1</option>
                            <option value="7">7 - RSASHA1-NSEC3-SHA1</option>
                            <option value="8">8 - RSASHA256</option>
                            <option value="10">10 - RSASHA512</option>
                            <option value="12">12 - ECC-GOST</option>
                            <option value="13">13 - ECDSAP256SHA256</option>
                            <option value="14">14 - ECDSAP384SHA384</option>
                        </select>
                    </td>
                    <td><input class="form-control" type="text" name="DNSSEC[{$smarty.foreach.ksdata.index+1}][pubkey]" value=""></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="text-left">
        <input class="btn btn-large btn-primary" type="submit" value="{$LANG.clientareasavechanges}">
    </p>
    </form>
