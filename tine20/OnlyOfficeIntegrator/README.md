# Security Considerations

## Attack Vectors

### documentmanager API
OO requires us as the documentmanager to have an API for reading and writing documents. The requests to this API include the `document path` and `user_id` of the current user and are signed by JWT with a `shared secret`.

In other words: An attacker knowing this `shared secret` is able to read/write ANY document (including data treasure and PIN protected apps) as ANY user at ANY time from ANYWHERE

There is no listing possible via this API. So an attacker needs to know the `document path` and a valid `user_id`. Both is easy to get!

### documenteditor
OO opens loads the document into it's own cache when the first user starts editing a document, identified by the `key` property. Any further editor of the same document directly enters the editor session. OO does not validate if the new user is able to download the document.

OO has a single representation for metadata of a document being edited. Each new user entering the editor session overwrites this metadata. With this, the last user who entered the editor session initiated the write requests.

In other words: An attacker knowing the `shared secret` and the `key` can open an editor session for documents being edited in OO at the moment from ANYWHERE.

@TODO: protect callback download urls?

## API Defence Actions:
### NETWORK PROTECTION
 
Limit API Access to OO Servers.

With a compromised OO installation an Attacker is able to read/write ANY document (including data treasure and PIN protected apps) as ANY user at ANY time

### ACCESS TOKENS

Access Tokens for documents are created when the first user opens a document. They validate `user_id`s and `document path`s an have a limited life time.

With a compromised OO installation an Attacker is able to read/write documents which are being edited by the service as one of the editing users at the time the access token is valid.

When a user opens a document in OO we create an access-token:
* randomid (this is shared with OO as the document path)
* userid (at our side only)
* docuemtid (at our side only)


#### Access Token invalidation
* user suscriptions for access tokens have a timestamp when they got created / last touched
* tokens are valid until this timestamp + a configurable timeout
* this timestamp is touched by a custom observer which runs in the client in the frequency of the timeout*0,8, reporting all open oo windows
* lifetime is revoked when user closes a documentwindow
* NOTE: do not mixup with presenceobserver. token remains valid also if user does not show presence until her session is over!
* access tokens are **not** bound to users sessions!
  * OO might want to save a document after the session is over (time can be configured)

## editor defence actions
### Use Access Token as `key`
Use the access token as part of `key` and `path`.

With a compromised OO installation an Attacker is able to read/write documents which are being edited by the service as one of the editing users at the time the access token is valid.


