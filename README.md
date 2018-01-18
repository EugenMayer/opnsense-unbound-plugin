## Unbound API for opnsense

This plugins adds the missing API to control unbound using a web-API. Yet not all operations are implemented ( help? ).

You can for now do:
 
  - CRUD host entries
  
**Those operations will be transparent to the actual (legacy) plugin and e.g. will show up in the UI**  
  
### Installation

```bash
export unbound_version=0.0.1
curl -Lo os-unbound-devel-${unbound_version}.txz https://github.com/EugenMayer/opnsense-unbound-plugin/raw/master/dist/os-unbound-devel-${unbound_version}.txz
pkg add os-unbound-devel-${unbound_version}.txz
```
    
### Using the API

Enable/install the plugin

#### Create / Update Host Entry

`POST` on `api/unbound/hostEntry/setHostEntry`
```
{
  "hostentry": { 
    "ip": "10.1.1.1",
    "domain": "foo.tld",
    "host": "bar"
  }
}
```

If a host with that `domain` and `host` already exists, and update will be done, otherwise it will be created 


#### Delete Host Entry

`POST`  on `api/unbound/hostEntry/delHostEntry`
- Payload
    ```
    {
      "hostentry": { 
        "ip": "10.1.1.1",
        "domain": "foo.tld",
        "host": "bar"
      }
    }
    ```

-> If the hostentry matching the given domain / host will be deleted


Alternatively by IP

`POST`  on `api/unbound/hostEntry/delHostEntryByIp`
- Payload
    ```
    {
      "hostentry": { 
        "ip": "10.1.1.1",
      }
    }
    ```

If the hostentry matching the given ip will be deleted

#### Get Host Entry(s)

`GET` on `api/unbound/hostEntry/getHostEntry` 
- This will return all host entries

`GET` on` api/unbound/hostEntry/getHostEntry/<host>|<domain>`
- *IMPORTANT* its an `|` and not a slash!! important.
- This will return you the hostEntry matching this host/domain. 

## Development

### Start

No magic involved here, fires up a vagrant build on the recent [opnsense-build](https://app.vagrantup.com/eugenmayer/boxes/opnsense)

```
make start
```

1. You see the plugin deployed in the opnsense instance, access it by https://localhost:10443
2. If you change code, just run `make sync_plugin`
3. Its all on you now :)

### Stop ( pause )
To stop the vm ( not losing state, continue later )
```   
make stop
```

### Rm ( end, remove all )
To remove the VM
```
make rm
```

## During development

### Plugins

If you change code of the plugin, run

    make sync_plugin