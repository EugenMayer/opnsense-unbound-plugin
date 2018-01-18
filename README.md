
This is just the fastes way to bring up a development ready opnsense box for you to start with

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