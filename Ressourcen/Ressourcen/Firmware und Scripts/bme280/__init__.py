from .lib import BME280 as native

class BME280:
    def __init__(self, bus, address):
        self._bme280 = native(i2c_dev=bus, i2c_addr=address)

    def read_temperature(self):
        return self._bme280.get_temperature()

    def read_pressure(self):
        return self._bme280.get_pressure()

    def read_humidity(self):
        return self._bme280.get_humidity()