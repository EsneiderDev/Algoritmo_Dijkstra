<?php

require 'PriorityQueue.php';


/**
 *  * Esta implementación del algoritmo de Dijkstra utiliza una lista de adyacencia.  Esto significa que
 *  * es mejor para grafos dispersos que para densos.
 *
 *  * Utiliza una cola de prioridad pero es capaz de manejar grafos dirigidos y no dirigidos.  Si se trata de
 * *  un grafo dirigido acortará el bucle, lo que es genial en términos de rendimiento.
 **  
 * *  El grafo se pasa por referencia, por lo que no se usa memoria extra.  Esto es bueno para grafos grandes.
 **  
 * *  Los cálculos se realizan
 *
 * * Suposiciones:
 * * - El grafo tiene nodos enteros.
 * * - Las distancias son enteros positivos (A hacer: comprobar pesos negativos).
 * 
 * 
 * This implementation of the Dijkstra algorithm uses an adjacency list.  This means that it
 * is better for sparse graphs rather than dense.
 *
 * It uses a priority queue but is able to manage directed and undirected graphs.  If it is
 * a directed graph it will shorten the loop, which is great in terms of performance.
 *
 * The graph is passed by reference, so no extra memory is used.  This is good for big graphs.
 *
 * Calculations are done
 *
 * Assumptions:
 *    - The graph has integer nodes.
 *    - The distances are positive integers (To Do: check negative weights).
 *
 * @author Oscar Pascual <oscar.pascual@gmail.com>
 */
class Dijkstra
{

    //* El gráfico, aunque es una referencia al original
    // The graph, although it is a reference to the original one
    protected $graph;

    //* La fuente.  Necesario para devolver el camino más corto.
    // The source.  Needed for returning the shortest path.
    protected $source;

    //* Distancias del nodo de origen a cada uno de los otros nodos
    // Distances from the source node to each other node
    protected $distances;

    //* Nodo(s) anterior(es) en la ruta hacia el nodo actual
    // The previous node(s) in the path to the current node
    protected $previous;

    //* Nodos pendientes de tratamiento
    // Nodes which have yet to be processed
    protected $queue;

    //* Tiempo de cálculo del algoritmo
    // Time of algorithm calculation
    protected $time;

    //* Conectividad de grafos: dirigida o no dirigida
    // Graph connectivity: directed or undirected
    protected $is_directed;

    //* Definir el valor infinito
    // Define infinite value
    const INT_MAX = 0x7FFFFFFF;


    /**
     * * Crear un nuevo objeto Dijkstra con grafo y nodo origen.
     * * Graph es una referencia al grafo exterior ya que sólo necesitamos leer su información.
     * * Si pasamos un grafo no dirigido tenemos margen de optimización.
     * 
     * Create a new Dijkstra object with graph and source node.
     * Graph is a reference to the outside graph as we only need to read its information.
     * If we pass an undirected graph we have room for optimization.  ;-)
     */
    public function __construct(&$graph, $source, $is_directed = false)
    {
        $this->graph       = $graph;
        $this->source      = $source;
        $this->queue       = new PriorityQueue();
        $this->is_directed = $is_directed;

        /**
         * * Inicializar las matrices 'distances' y 'previous', y la cola de prioridad.
         * Initialize the 'distances' and 'previous' arrays, and the priority queue.
         */
        $this->queue->push($source, 0);
        foreach($graph as $origin => $vertices) {
            $this->distances[$origin] = self::INT_MAX;
            $this->previous[$origin]  = null;
            if ($origin != $source) {
                $this->queue->push($origin, self::INT_MAX);
            }
        }
        $this->distances[$source] = 0;


        /**
         * * ¡Y aquí empieza el algoritmo!  :-)
         * And here starts the algorithm!  :-)
         */
        $start = microtime(true);
        while (!$this->queue->isEmpty()) {
            $current = $this->queue->pop();

            $neighborsOfCurrent = $this->getNeighbors($current);

            foreach($neighborsOfCurrent as $neighbour => $distance) {// $distancia = longitud($actual, $vecino)
                $alt = $this->distances[$current] + $distance;      // $distance = length($current, $neighbour)
                if ($alt < $this->distances[$neighbour]) {
                    $this->distances[$neighbour] = $alt;
                    $this->previous[$neighbour] = $current;
                    $this->queue->change_priority($neighbour, $alt);
                }
            }
        }
        $this->time = round(microtime(true) - $start, 4);
    }


    /**
     * * Obtener los vecinos de un nodo concreto.
     * * Si el grafo no está dirigido, existe una optimización: eliminar de la cola los nodos visitados anteriormente.
     * 
     * Obtain neighbors for one particular node.
     * If the graph is undirected, there is an optimization: eliminate previously visited nodes
     * from the queue.
     *
     * @param Int $origin
     * @return Void
     */
    function getNeighbors($origin)
    {
        //* Si un grafo es dirigido, simplemente devuelve todos sus vecinos.
        // If a graph is directed, simply return all its neighbors.
        if ($this->is_directed) {
            return $this->graph[$origin];
        }

        //* Si un grafo es no dirigido, podemos eliminar los nodos eliminados anteriormente.
        // If a graph is undirected, then we can eliminate the previously eliminated nodes.
        $allNeighbors   = $this->graph[$origin];
        $validNeighbors = array();

        //* Obtener sólo vecinos no visitados.
        // Get only non-visited neighbors.
        foreach($allNeighbors as $neighborId => $distance) {
            if ($this->queue->contains($neighborId)) {
                $validNeighbors[$neighborId] = $distance;
            }
        }

        return $validNeighbors;
    }


    /**
     * * Devuelve un array con el camino más corto a un destino específico
     * * con la siguiente estructura:
     * 
     * Returns an array with the shortest path to a specific destination
     * with the following structure:
     * [0] => [source, 0]
     * [1] => [node 1, cost]
     * [2] => [node 2, accumulated cost]
     * ...
     * [n] => [destination, total cost]
     *
     * @param Int $destination
     * @return Array
     */
    public function shortestPathTo($destination)
    {
        $shortest_path = array();

        //* Introducir el destino en la matriz del camino más corto.
        // Introduce destination into the shortest path array.
        $shortest_path[] = [
            'node_identifier' => $destination,
            'weight' => 0,
            'accumulated_weight' => $this->distances[$destination],
        ];

        //* Selecciona el nodo anterior y realiza un bucle hasta encontrar la fuente.
        // Select previous node and loop until source is found.
        $previous_node = $this->previous[$destination];
        while ($previous_node != $this->source) {
            //* ¿No es la fuente?  Empuje en la matriz, pero en su lugar [0]
            // Not the source?  Push into the array, but in place [0]
            array_unshift($shortest_path, [
                'node_identifier' => $previous_node,
                'weight' => 0,
                'accumulated_weight' => $this->distances[$previous_node],
            ]);
            //* Fijar el peso de nodo a nodo
            // Set node-to-node weight
            $shortest_path[1]['weight'] = $shortest_path[1]['accumulated_weight'] -$shortest_path[0]['accumulated_weight'];

            $previous_node = $this->previous[$previous_node];
        }

        //* Se ha encontrado la fuente.  Introducir en la posición [0] de la matriz de resultados.
        // Source is found.  Introduce into position [0] of the result array.
        array_unshift($shortest_path, [
            'node_identifier' => $this->source,
            'weight' => 0,
            'accumulated_weight' => 0,
        ]);
        $shortest_path[1]['weight'] = $shortest_path[1]['accumulated_weight'] -$shortest_path[0]['accumulated_weight'];


        return $shortest_path;
    }


    /**
     * *  Devuelve el tiempo empleado en ejecutar el algoritmo (sólo el bucle, sin la inicialización)
     * Return the time spent to run the algorithm (only the loop, without the initialization)
     *
     * @return Float
     */
    public function getAlgorithmTime()
    {
        return $this->time;
    }


    /**
     * * Devuelve el array 'distancias'.
     * Return the 'distances' array.
     *
     * @return Array
     */
    public function getDistances()
    {
        return $this->distances;
    }


    /**
     * * Devuelve el array 'anterior'.
     * Return the 'previous' array.
     *
     * @return Array
     */
    public function getPrevious()
    {
        return $this->previous;
    }


}
